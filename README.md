# anthro-inner-path

Rudolf Steiner's exercises for the inner path, arranged for practice, at
[orenknaan.co.il/anthro-inner-path](https://orenknaan.co.il/anthro-inner-path/).

A single static page, no build step: the repository root maps to the site's
`/anthro-inner-path/` folder.

```
index.html   the page (self-contained: CSS inline, RTL Hebrew)
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

## Deploying

Upload the working tree over FTP to `public_html/anthro-inner-path/`.
