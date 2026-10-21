# anthro-inner-path

Rudolf Steiner's exercises for the inner path, arranged for practice, at
[orenknaan.co.il/anthro-inner-path](https://orenknaan.co.il/anthro-inner-path/).

A single static page, no build step: the repository root maps to the site's
`/anthro-inner-path/` folder.

```
index.html   the page (self-contained: CSS and JS inline, RTL Hebrew)
counter.php  the live visitor counter's endpoint (PHP + SQLite)
fonts/       self-hosted Assistant + Noto Serif Hebrew, copied from /clarity/
```

The page has two parts:

1. **The six subsidiary exercises** (ששת התרגילים המשניים), one per month,
   transcribed from a printed Hebrew handout.
2. **The eight exercises** (שמונת התרגילים) for the sixteen-petalled lotus,
   one per weekday from Saturday plus the eighth daily. A new Hebrew
   translation of the German original, GA 10 *Wie erlangt man Erkenntnisse der
   höheren Welten?*, chapter "Über einige Wirkungen der Einweihung",
   paragraphs 6-10 ([rsarchive.org](https://rsarchive.org/Books/GA010/German/GA010_c06.html)).

Each part is a `<section class="part">` with the same part-head / months /
`.ex` structure; a new part follows the same pattern and gets a link in
`nav.parts`.

## Visitor counter

The sticky nav shows unique visitors and visits, and polls `counter.php`
every 10 seconds while the page is open, so the numbers grow as people
arrive. A visitor is one browser (a random id in localStorage); a visit is a
run of views with no gap over 30 minutes. Crawlers are skipped, and one
address can add at most 10 new visitors a day. No address, user agent or
browsing history is stored.

The database is written to `anthro-inner-path-private/`, created
automatically next to `public_html` (two levels above `counter.php`),
because this host ignores `.htaccess` and anything in the web root can be
downloaded. If that folder can't be created outside the web root, the
counter answers 503 and simply stays hidden on the page.

To test locally: `ANTHRO_COUNTER_DIR=/some/tmp/dir php -S localhost:8000`
from the folder above this one, then open `/anthro-inner-path/`.

## Deploying

Upload the working tree over FTP to `public_html/anthro-inner-path/`
(`index.html`, `counter.php`, `fonts/`). Don't put `.git/` in the web root.
