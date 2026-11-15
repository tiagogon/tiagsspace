# Installing the redirects snippet on the server

`tiagsspace-redirects.conf` is generated from `library/redirects.php`; never edit it by hand:

```bash
/Applications/MAMP/bin/php/php8.2.0/bin/php bin/redirects-nginx.php > server/nginx/tiagsspace-redirects.conf
```

A push to `main` updates the theme but **not nginx**. The file under `/etc/nginx` is a copy that is installed by hand (sudo). nginx never includes it from the theme folder, so a bad commit cannot break a reload.

## First installation (Cidade release, Nov 2026)

Done by Tiago, over `ssh -t -p 4444 tiago@198.211.120.201`, once the theme with `library/redirects.php` is deployed.

1. Back up the vhost and install the snippet:

   ```bash
   sudo cp /etc/nginx/sites-available/tiagsspace /etc/nginx/sites-available/tiagsspace.bak-$(date +%Y%m%d-%H%M%S)
   ```

   ```bash
   sudo cp /var/www/tiagsspace/wp-content/themes/tiagsspace/server/nginx/tiagsspace-redirects.conf /etc/nginx/snippets/tiagsspace-redirects.conf
   ```

2. Edit `/etc/nginx/sites-available/tiagsspace` (`sudo nano`). In the `server_name tiags.space;` block on port 443:

   - Add the include **just before** the W3TC include:

     ```diff
     +    # Redirects of this installation (generated from the theme's library/redirects.php)
     +    include snippets/tiagsspace-redirects.conf;
     +
          include /var/www/tiagsspace/nginx.conf;
     ```

   - Remove the old loose redirect lines (they sit after the W3TC include, so W3TC's cache won over them, and they are now in the snippet or in PHP):

     ```diff
          # Custom redirect rules
          rewrite ^/stories/(.*)$ /?p=$1 last;
          rewrite ^/storie/(.*)$ /?p=$1 last;
     -    rewrite ^/post/(.*)$ https://tiags.space/ redirect;
     -    rewrite ^/sidewalk/(.*)$ https://tiags.space/log/$1 redirect;
     -    rewrite ^/branch/(.*)$ https://tiags.space/log-branch/$1 redirect;
     -
     -    location = /log-branch/      { return 301 /log/; }
     -    location = /log-branch       { return 301 /log/; }
     -    location = /log-branch/nxty/ { return 301 /log-branch/blwww/; }
     -    location = /log-branch/nxty  { return 301 /log-branch/blwww/; }
     ```

     `/stories/` and `/storie/` stay: they are internal rewrites, not redirects. `/post/<id>` is now answered by WordPress (`library/redirects.php`): a restored Urbanoid post by its old id, otherwise the home page as before.

3. Test, reload, purge the page cache (it holds stale `/cityburns/` pages):

   ```bash
   sudo nginx -t && sudo systemctl reload nginx
   ```

   ```bash
   sudo -u www-data wp w3-total-cache flush all --path=/var/www/tiagsspace </dev/null
   ```

4. Check (each should print 301 and the new address, except the last two):

   ```bash
   for p in /cityburns/ /cityburns/higluk/ "/cityburns/marla-%c2%a7-lissabon/" /sidewalk/x/ /branch/blwww/ /log-branch/ /log-branch/nxty/ /post/1/ /stories/1; do printf '%-36s ' "$p"; curl -s -o /dev/null -w '%{http_code} %{redirect_url}\n' "https://tiags.space$p"; done
   ```

   `/post/1/` gives 302 to the home page, `/stories/1` stays an internal rewrite.

To undo: put the backup back (`sudo cp /etc/nginx/sites-available/tiagsspace.bak-… /etc/nginx/sites-available/tiagsspace`), `sudo nginx -t && sudo systemctl reload nginx`. The PHP layer keeps answering every fixed redirect meanwhile.

## Later changes

Edit the list in `library/redirects.php`, regenerate the snippet, commit both, deploy, then repeat steps 1 (the `cp` of the snippet only), 3 and 4.

## Not part of this change (noted 15 Nov 2026)

- `cityburns.com` is declared in two enabled files (`sites-available/tiagsspace`, last two server blocks, and `sites-available/cityburns`); nginx uses one and ignores the other (`conflicting server name` warning).
- The "Debug logging for WordPress previews" location (`~* /(\?.*p=.*&preview=true)`) can never match: a location does not see the query string.
- 15 `.bak` files sit in `sites-available`.
