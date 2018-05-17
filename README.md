# PHP FE Builder
A tiny PHP script for concatenating and minifying a JS/CSS build on the go.
- It checks all js/css files in its directory and sub-directories for changes
- It is run via **terminal/shell** `php run.php` in the background
- By default it outputs two files: `js/app.min.js` and `css/app.min.css`
- This script was written and tested on **macOS High Sierra**

## Requirements
This small PHP script is required: (https://github.com/mrclay/jsmin-php/blob/master/src/JSMin/JSMin.php). The script has to be in same directory as `run.php`. 

## License
This project is distributed under the MIT license. See `LICENSE`.