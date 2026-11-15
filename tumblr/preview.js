#!/usr/bin/env node
/*
 * Local preview of tumblr/theme.html.
 *
 * Tumblr has no local renderer, so this fills the theme's {block:…} and
 * {Variable} tags with the blog's real, public posts (Tumblr's read API) and
 * writes static pages to tumblr/preview/ (gitignored):
 *
 *   node tumblr/preview.js            # the first index pages (15 posts each)
 *   node tumblr/preview.js --start 15 # start one page further back ("page 2")
 *
 *   http://localhost:8888/tiagsspace/wp-content/themes/tiagsspace/tumblr/preview/index.html
 *
 * It is an approximation: it knows the tags the theme uses, not all of Tumblr's.
 * The last check is always Tumblr's own preview in Edit theme.
 */
'use strict';

const fs = require('fs');
const path = require('path');

const BLOG = 'tiagssssspace';
const HERE = __dirname;
const OUT = path.join(HERE, 'preview');
const MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
	'August', 'September', 'October', 'November', 'December'];

const startArg = process.argv.indexOf('--start');
const START = startArg > -1 ? parseInt(process.argv[startArg + 1], 10) || 0 : 0;
const PER_PAGE = 15; // the blog's posts per page (Settings), so {block:Post1}…{block:Post15} line up
const PAGES = 4;     // index pages built, so infinite scroll has somewhere to go

/* ---------- a small Tumblr template renderer ---------- */

// {block:Name}…{/block:Name}: dropped when the context value is falsy, repeated
// for an array, rendered once otherwise. {Name} is replaced only when the
// context knows it, so CSS and JavaScript braces pass through untouched.
function render(tpl, ctx) {
	let out = '';
	let rest = tpl;
	for (;;) {
		const open = rest.match(/\{block:([A-Za-z0-9]+)\}/);
		if (!open) {
			break;
		}
		const name = open[1];
		const close = '{/block:' + name + '}';
		const from = open.index + open[0].length;
		const to = rest.indexOf(close, from);
		if (to < 0) {
			throw new Error('Unclosed {block:' + name + '}');
		}
		out += vars(rest.slice(0, open.index), ctx);
		const inner = rest.slice(from, to);
		const value = ctx[name];
		if (Array.isArray(value)) {
			out += value.map((item) => render(inner, Object.assign({}, ctx, item))).join('');
		} else if (value && typeof value === 'object') {
			out += render(inner, Object.assign({}, ctx, value));
		} else if (value) {
			out += render(inner, ctx);
		}
		rest = rest.slice(to + close.length);
	}
	return out + vars(rest, ctx);
}

function vars(text, ctx) {
	return text.replace(/\{([A-Za-z][A-Za-z0-9-]*)\}/g, (all, name) =>
		typeof ctx[name] === 'string' || typeof ctx[name] === 'number' ? String(ctx[name]) : all);
}

/* ---------- the blog's posts, as theme variables ---------- */

// The read API prints photo rows a little differently from the theme engine.
// Rebuild what {Body} gives on the live blog: .npf_row > .npf_col > figure > a > img
function liveMarkup(body) {
	return body.replace(
		/<figure class="tmblr-full" data-orig-height="(\d+)" data-orig-width="(\d+)">(<img[\s\S]*?)<\/figure>/g,
		(all, h, w, img) => '<div class="npf_col"><figure class="tmblr-full">' +
			'<a class="post_media_photo_anchor" data-big-photo-width="' + w + '" data-big-photo-height="' + h + '">' +
			img + '</a></figure></div>'
	);
}

function postContext(p) {
	const d = new Date(p['unix-timestamp'] * 1000);
	const pad = (n) => String(n).padStart(2, '0');
	const tags = (p.tags || []).map((t) => ({ Tag: t, TagURL: '#tag-' + encodeURIComponent(t) }));
	const ctx = {
		PostID: p.id,
		PostType: p.type === 'regular' ? 'text' : p.type,
		Permalink: 'post-' + p.id + '.html',
		ReblogURL: p['url-with-slug'],
		Date: {
			Year: d.getFullYear(),
			MonthNumberWithZero: pad(d.getMonth() + 1),
			DayOfMonthWithZero: pad(d.getDate()),
			DayOfMonth: d.getDate(),
			Month: MONTHS[d.getMonth()],
			TimeAgo: Math.max(1, Math.round((Date.now() - d) / 864e5)) + ' days ago',
		},
		HasTags: tags.length > 0,
		Tags: tags,
		NoteCount: +p['note-count'] > 0,
		NoteCountWithLabel: p['note-count'] + (+p['note-count'] === 1 ? ' note' : ' notes'),
	};
	if (p.type === 'regular') {
		ctx.Text = {
			Title: p['regular-title'] ? { Title: p['regular-title'] } : false,
			Body: liveMarkup(p['regular-body'] || ''),
		};
		ctx._title = p['regular-title'] || '';
	} else if (p.type === 'photo') {
		const photos = (p.photos && p.photos.length ? p.photos : [p]).map((ph) => ({
			'PhotoURL-HighRes': ph['photo-url-1280'],
			'PhotoWidth-HighRes': ph.width,
			'PhotoHeight-HighRes': ph.height,
			PhotoAlt: '',
			LinkOpenTag: '',
			LinkCloseTag: '',
		}));
		const caption = p['photo-caption'] ? { Caption: p['photo-caption'] } : false;
		if (photos.length > 1) {
			ctx.Photoset = { Photos: photos, Caption: caption };
		} else {
			ctx.Photo = Object.assign({ Caption: caption }, photos[0]);
		}
	} else if (p.type === 'quote') {
		ctx.Quote = { Quote: p['quote-text'], Source: p['quote-source'] ? { Source: p['quote-source'] } : false };
	} else if (p.type === 'link') {
		ctx.Link = {
			URL: p['link-url'], Name: p['link-text'] || p['link-url'], Target: '',
			Description: p['link-description'] ? { Description: p['link-description'] } : false,
		};
	} else if (p.type === 'video') {
		ctx.Video = { 'Video-700': p['video-player'] || '', Caption: p['video-caption'] ? { Caption: p['video-caption'] } : false };
	} else if (p.type === 'audio') {
		ctx.Audio = { 'AudioEmbed-640': p['audio-embed'] || p['audio-player'] || '', Caption: p['audio-caption'] ? { Caption: p['audio-caption'] } : false };
	}
	return ctx;
}

async function main() {
	const res = await fetch('https://' + BLOG + '.tumblr.com/api/read/json?num=' + (PER_PAGE * PAGES) + '&start=' + START);
	const text = await res.text();
	const data = JSON.parse(text.replace(/^var tumblr_api_read = /, '').replace(/;\s*$/, ''));
	const posts = data.posts.map(postContext);

	let theme = fs.readFileSync(path.join(HERE, 'theme.html'), 'utf8');
	// what Tumblr adds to every blog page by itself
	theme = theme.replace('</head>',
		'<link rel="stylesheet" href="https://assets.tumblr.com/client/prod/standalone/blog-network-npf/index.build.css">\n</head>');

	theme = theme.replace(/\{color:([^}]+)\}/g, (all, name) => {
		const meta = theme.match(new RegExp('<meta name="color:' + name + '" content="([^"]*)"'));
		return meta ? meta[1] : all;
	});

	const blog = {
		Title: data.tumblelog.title,
		Name: data.tumblelog.name,
		Favicon: '',
		RSS: 'https://' + BLOG + '.tumblr.com/rss',
		Description: true,
		MetaDescription: String(data.tumblelog.description || '').replace(/<[^>]+>/g, ''),
		CustomCSS: '',
		AskEnabled: { AskLabel: 'Ask' },
	};

	fs.mkdirSync(OUT, { recursive: true });

	// index.html, page-2.html, page-3.html…: the "2" in the second name is what
	// the theme's infinite scroll reads to number the pages after it.
	const pageFile = (n) => (n === 1 ? 'index.html' : 'page-' + n + '.html');
	const pages = Math.ceil(posts.length / PER_PAGE);
	let index = '';
	for (let n = 1; n <= pages; n++) {
		const html = render(theme, Object.assign({}, blog, {
			IndexPage: true,
			Posts: posts.slice((n - 1) * PER_PAGE, n * PER_PAGE)
				.map((post, i) => Object.assign({}, post, { ['Post' + (i + 1)]: true })),
			NextPage: n < pages ? { NextPage: pageFile(n + 1) } : false,
			PreviousPage: n > 1 ? { PreviousPage: pageFile(n - 1) } : false,
		}));
		fs.writeFileSync(path.join(OUT, pageFile(n)), html);
		if (n === 1) {
			index = html;
		}
	}

	posts.forEach((post, i) => {
		const page = render(theme, Object.assign({}, blog, {
			PermalinkPage: true,
			Posts: [post],
			PostTitle: post._title ? { PostTitle: post._title } : false,
			PostNotes: false,
			PermalinkPagination: {
				PreviousPost: posts[i + 1] ? { PreviousPost: posts[i + 1].Permalink } : false,
				NextPost: posts[i - 1] ? { NextPost: posts[i - 1].Permalink } : false,
			},
		}));
		fs.writeFileSync(path.join(OUT, post.Permalink), page);
	});

	const left = (index.match(/\{\/?block:[^}]*\}/g) || []);
	console.log('Wrote ' + pages + ' index pages and ' + posts.length + ' post pages to ' + OUT);
	if (left.length) {
		console.log('Unrendered block tags: ' + Array.from(new Set(left)).join(' '));
	}
}

main().catch((err) => {
	console.error(err.message);
	process.exit(1);
});
