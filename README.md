# Accelerated Mobile Pages (AMP)

## Requirements

* [AMP Theme](https://www.drupal.org/project/amptheme)
* [AMP PHP Library](https://github.com/Lullabot/amp-library)
* PHP version 5.5.+


## Introduction

The AMP module is designed to convert Drupal pages into pages that comply with the AMP standard. Initially only node pages will be converted. Other kinds of pages will be enabled at a later time.

When the AMP module is installed, AMP can be enabled for any node type. At that point, AMP content becomes available on URLs such as `node/1?amp` or `node/article-title?amp`. There are also special AMP formatters for text, image, and video fields.

The [AMP Theme](https://www.drupal.org/project/amptheme) is designed to produce the very specific markup that the AMP HTML standard requires. The AMP theme is triggered for any node delivered on an `?amp` path. As with any Drupal theme, the AMP theme can be extended using a subtheme, allowing publishers as much flexibility as they need to customize how AMP pages are displayed. This also makes it possible to do things like place AMP ad blocks on the AMP page using Drupal's block system.

The [AMP PHP Library](https://github.com/Lullabot/amp-library) analyzes HTML entered by users into rich text fields and reports issues that might make the HTML non-compliant with the AMP standard.  The library does its best to make corrections to the HTML, where possible, and automatically converts images and iframes into their AMP HTML equivalents. More automatic conversions will be available in the future. The PHP Library is CMS agnostic, designed so that it can be used by both the Drupal 8 and Drupal 7 versions of the Drupal module, as well as by non-Drupal PHP projects.

We have done our best to make this solution as turnkey as possible, but the module, in its current state, is not feature complete. At this point only node pages can be converted to AMP HTML. The initial module supports AMP HTML tags such as `amp-ad`, `amp-pixel`, `amp-img`, `amp-video`, `amp-analytics`, and `amp-iframe`, but we plan to add support for more of the extended components in the near future. For now the module supports Google Analytics, AdSense, and DoubleClick
for Publisher ad networks, but additional network support is forthcoming.


## Supported AMP Components

- [amp-ad](https://www.ampproject.org/docs/reference/amp-ad.html)
- [amp-pixel](https://www.ampproject.org/docs/reference/amp-pixel.html)
- [amp-img](https://www.ampproject.org/docs/reference/amp-img.html)
- [amp-video](https://www.ampproject.org/docs/reference/amp-video.html)
- [amp-analytics](https://www.ampproject.org/docs/reference/extended/amp-analytics.html)
- [amp-iframe](https://www.ampproject.org/docs/reference/extended/amp-iframe.html)

Support for additional [extended components](https://www.ampproject.org/docs/reference/extended.html) is forthcoming.


## Module Architecture Overview

The module will be responsible for the basic functionality of providing an AMP version of Drupal pages. It will:

- Create an AMP view mode, so users can identify which fields in which order should be displayed on the AMP version of a page.
- Create an AMP route, which will display the AMP view mode on an AMP path (i.e. `node/1?amp`).
- Create AMP formatters for common fields, like text, image, video, and iframe that can be used in the AMP view mode to display AMP-compatible markup for those fields.
- Create AMP ad blocks that can be placed by the theme.
- The theme can place AMP pixel items in the page markup where appropriate, based on the configuration options.
- Create an AMP configuration page where users can identify which ad and analytics systems to use, and identify which theme is the AMP theme.
- Create a way for users to identify which content types should provide AMP pages, and a way to override individual nodes to prevent them from being displayed as AMP pages (to use for odd pages that wouldn’t transform correctly).
- Make sure that paths that should not work as AMP pages generate 404s instead of broken pages.
- Make sure that aliased paths work correctly, so if `node/1` has an alias of `my-page`, `node/1?amp` has an alias of `my-page?amp`.
- Create a system so the user can preview the AMP page.

The body field presents a special problem, since it is likely to contain lots of invalid markup, especially embedded images, videos, tweets, and iframes. There is no easy way to convert a blob of text with invalid markup into AMP-compatible markup. At the same time, this is a common problem that other projects will run into. Our solution is to create a separate, stand-alone, [AMP PHP Library](https://github.com/Lullabot/amp-library) to transform that markup, as best it can, from non-compliant HTML to AMP HTML. The AMP formatter for the body will use that library to render the body in the AMP view mode.


## Initial setup

* Follow instructions at https://www.drupal.org/node/2405811 to get Composer Manager working.
* Download the AMP theme into the `/themes` directory.
* Download this (AMP) module into the `/modules` directory.
* On the command line, move to the root of the Drupal project and run `composer drupal-update` to pick up remaining dependencies.


## AMP Configuration

* Install the AMP Base theme and the ExAMPle subtheme included in amptheme, but don't make it the default theme. It will only be used on AMP pages.
  * With Drush, you can install both with the command `drush en ampsubtheme_example`, because amptheme is set as the base theme for ampsubtheme_example.
* Enable the AMP module. This should be done after the AMP themes have been installed.
  * Go to `/admin/config/content/amp` and select configuration options.
  * Identify which content types you want to create AMP pages and which theme you will use on those pages. By default, the ExAMPle Subtheme should be selected. You can create a custom theme that is based on the AMP Base theme, in order to provide custom markup and styles. If that custom subtheme is installed, you can select it here.
  * If you use Google AdSense or Google Analytics you can also provide the IDs that will be used to create ads and analytics on the AMP pages.
  * Select and save the options.
* Go to `/admin/structure/block/list/{AMP-THEME-NAME}` and set up the blocks for the AMP page.
  * The AMP page is a simple page, with a header, content area, and footer. You should remove most blocks from this theme. We suggest just displaying the branding, title and content on the page. Start simple and add more elements later if desired.
  * If you want ads on your AMP pages, add AMP Ad blocks as desired. The ads will use the IDs provided in your AMP configuration.
* Go to `/admin/structure/types/manage/{CONTENT-TYPE}/display/amp` and set up the fields for the AMP version of each content type.
  * The AMP view mode is where you can control which fields will display on the AMP page for each content type. You might only want a title, image, and body.
  * There are special AMP formatters for text, image, and iframe fields, so be sure to use them in the AMP view mode.

### Content Types
* Find the list of your content types at the top
* Click the link to "Enable AMP in Custom Display Settings"
* Open "Custom Display Settings" fieldset, check AMP, click Save button (this brings you back to the AMP config form)
* Click "Configure AMP view mode"
* Set your Body field to use the "AMP text" format (and any other fields you want to configure)
* Click Save button (this brings you back to the AMP config form)

### Analytics (optional)
* Enter your Google Analytics Web Property ID and click save
* This will automatically be added to the AMP version of your pages

### Adsense (optional)
* Enter your Google AdSense Publisher ID and click save
* Visit `/admin/structure/block` to add and configure Adsense blocks to your layout
* Each block requires a Width, Height, and Data-slot

### DoubleClick (optional)
* Enter your Google DoubleClick for Publishers Network ID and click save
* Visit `/admin/structure/block` to add and configure add DoubleClick blocks to your layout
* Each block requires a Width, Height, and Data-slot

### AMP Pixel (optional)
* Check the "Enable amp-pixel" checkbox
* Fill out the domain name and query path boxes
* Click save


## Current maintainers:

- Matthew Tift - https://www.drupal.org/u/mtift
- Marc Drummond - https://www.drupal.org/u/mdrummond
- Sidharth Kshatriya - https://www.drupal.org/u/sidharth_k
