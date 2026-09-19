#!/usr/bin/env bash
# AI-crawler report for tiags.space — hits per user-agent and status from the nginx access logs.
# Run on the production server:  bash bin/ai-bot-report.sh [days]   (default 30)
# The pattern mirrors /etc/nginx/conf.d/ai-training-bots.conf + the allow list in robots.txt.
# Refresh both lists quarterly from github.com/ai-robots-txt/ai.robots.txt.
set -euo pipefail
DAYS="${1:-30}"
LOGS=(/var/log/nginx/access.log*)
TRAIN='GPTBot|ClaudeBot|anthropic-ai|CCBot|Applebot-Extended|meta-externalagent|FacebookBot|Amazonbot|Bytespider|TikTokSpider|cohere-ai|cohere-training-data-crawler|AI2Bot|Ai2Bot-Dolma|Diffbot|omgili|ImagesiftBot|img2dataset|LAION|Timpibot|PanguBot|MistralAI-Training|Webzio|GoogleOther|PetalBot|Scrapy|Crawl4AI|FirecrawlAgent|OAI-AdsBot|bedrockbot'
SEARCH='OAI-SearchBot|ChatGPT-User|Claude-SearchBot|Claude-User|PerplexityBot|Perplexity-User|DuckAssistBot|MistralAI-User|Amzn-SearchBot|Kimi-SearchBot|YouBot|Andibot|ExaBot|Bravebot|Google-Agent|NotebookLM|Meta-ExternalFetcher|kagi-fetcher|Googlebot|Bingbot|Applebot|DuckDuckBot'
SINCE=$(date -d "-${DAYS} days" +%s)
report() {
  local label="$1" pat="$2"
  echo "== $label (last $DAYS days) =="
  zcat -f "${LOGS[@]}" 2>/dev/null \
  | awk -v since="$SINCE" -v pat="$pat" '
      BEGIN { m["Jan"]=1;m["Feb"]=2;m["Mar"]=3;m["Apr"]=4;m["May"]=5;m["Jun"]=6;m["Jul"]=7;m["Aug"]=8;m["Sep"]=9;m["Oct"]=10;m["Nov"]=11;m["Dec"]=12 }
      {
        # [19/Sep/2026:12:34:56 +0000]
        split($4, d, /[\/:\[]/); ts = mktime(d[4]" "m[d[3]]" "d[2]" "d[5]" "d[6]" "d[7]);
        if (ts < since) next;
        ua = $0; sub(/.*" "/, "", ua); sub(/".*/, "", ua);
        if (match(ua, pat)) { name = substr(ua, RSTART, RLENGTH); c[name" "$9]++ }
      }
      END { for (k in c) printf "%8d  %s\n", c[k], k }' \
  | sort -rn
}
report "AI training / scraping crawlers (expect 403 once the nginx wall is live)" "$TRAIN"
report "Search and AI-search / user fetchers (expect 200)" "$SEARCH"
