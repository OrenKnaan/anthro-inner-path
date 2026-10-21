# anthro-inner-path

Rudolf Steiner's exercises for the inner path, arranged for practice, at
[orenknaan.co.il/anthro-inner-path](https://orenknaan.co.il/anthro-inner-path/).

A single static page, no build step: the repository root maps to the site's
`/anthro-inner-path/` folder.

```
index.html   the page (self-contained: CSS inline, RTL Hebrew)
fonts/       self-hosted Assistant + Noto Serif Hebrew, copied from /clarity/
```

The page holds the six subsidiary exercises (ששת התרגילים המשניים), one per
month. A second part is still to be added: give it its own
`<section class="part">` with the same structure, and turn its placeholder
entry in `nav.parts` into a link.

## Deploying

Upload the working tree over FTP to `public_html/anthro-inner-path/`.
