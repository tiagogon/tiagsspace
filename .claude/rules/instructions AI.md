---
description: Describe when these instructions should be loaded
paths:
. - "src/**/*.ts"
---
Never edit the bootstrap.css file. All style editing needs to be done via .scss files.
Never edit files inside library/js/ subdirectories (e.g. intense-images, plyr, masonry, etc.). These are third-party libraries that may be updated from their upstream repositories — any custom edits would be lost on update. Instead, apply fixes or overrides via custom SCSS or separate wrapper scripts.

never check how the SCSS is compiled. i will always ensure i have koala running on the backgroun when working with you
