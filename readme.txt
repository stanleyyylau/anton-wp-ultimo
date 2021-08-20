=== WP Ultimo ===
Contributors: aanduque
Requires at least: 4.5
Tested up to: 5.6
Requires PHP: 5.6
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Complete Network Solution.

== Description ==

WP Ultimo

The Complete Network Solution.

Brazilian Portuguese Translation: Fernando Arbex, Juliana Dias
Portuguese (Portugal) Translation: Marcos Lisboa
Vietnamese Translation: Richard Tuan
Norwegian Translation: Jøran Sørbø
Dutch Translation: Aron Prins & PG91
French Translation: nouvelletechno
Spanish Translation: Matias Candia
Russian Translation: Oleg Funbayu
Turkish Translation: Erdem Çilingiroğlu (partial)

This plugins includes the Mercator Domain Mapping solution, by humanmade: https://github.com/humanmade/Mercator/

== Installation ==

1. Upload 'wp-ultimo' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow the step by step Wizard to set the plugin up

== Changelog ==

Version 1.10.13 - 09/12/2020

* Fixed: CSS incompatibility issue with Uncanny Automator;
* Fixed: issue with users being able to site templates when copying their own sites;

Version 1.10.12 - 01/10/2020

* Fixed: Save Settings cleans up the API key field on the settings page;
* Fixed: Incompatibility with Elementor versions >= 3.0.10;

Version 1.10.11 - 08/05/2020

* Fixed: Iframes created by page builders with wrong document.domain set breaking;
* Fixed: Signup form fields with capped width;
* Fixed: wu_get_subscription_by_integration_key missing variable and not working properly;

Version 1.10.10 - 27/04/2020

* Fixed: Added window.domain fix for site templates to prevent CORS (thanks, Andrey);
* Fixed: Elementor compiled stylesheets missing when "copy media on duplication" is disabled;

Version 1.10.9 - 27/03/2020

* Fixed: SSO not working well with generic server name configs;

Version 1.10.8 - 26/03/2020

* Fixed: Incompatibility with iThemes Security, which warned about our Register page being insecure;
* Fixed: Keys on wp-activation.php (on user invite) not working with mapped domains;
* Fixed: Scroll issue on template previews on iPhones;

Version 1.10.7 - 16/03/2020

* Fixed: Force redirect to the main domain not working;
* Improved: Replaced the H1 element with an H3 on the Template List shortcode;

Version 1.10.6 - 20/02/2020

* Fixed: Dealing with leading and trailing slashes on the register and login URL settings;
* Fixed: Removed HTML tags from the PayPal description of the subscription;
* Fixed: Subsites not redirecting to the mapped domain in some specific edge-cases;
* Fixed: Reordering signup steps on the customizer breaks the template selection shortcode;
* Fixed: Coupon codes exclusive to Setup Fees (not the subscription price) incorrectly marking subscriptions as free;
* Fixed: Gutenberg incompatibility with mapped domains when the network domain is forced;
* Improved: Better compatibility with Jetpack;

Version 1.10.5 - 03/12/2019

* Fixed: Typos;
* Fixed: Coupon codes not being applied on Stripe in certain edge cases;
* Fixed: Duplication issues on Percona 8;
* Fixed: Customers getting a permission error when trying to access the Delete Account screen;
* Fixed: Issues with the login form on WordPress 5.3;
* Fixed: Warning on add_submenu_page with WordPress 5.3;
* Fixed: Free plan showing plan amount on the Account page;
* Fixed: Deleting site as a customer was not freeing up the site quota;
* Fixed: Broadcasts not being sent when the target was a single user;
* Fixed: WP Ultimo creating empty clients on Stripe when the Accounts page is accessed;
* Improved: Removed the gateway column from Billing History widget;
* Improved: Removed one-time charges since they are not allowed under SCA-compliance;

Version 1.10.4 - 20/09/2019

* Fixed: Typos;
* Fixed: Wrong discount value being displayed for coupons;
* Fixed: RunCloud integration now works with their API version 2.0 - Auto-SSL support was also added;
* Improved: Replaced the old Stripe Checkout with the new version, which supports SCA;

Version 1.10.3 - 27/08/2019

* Improved: Adjustments to add support to setup fee on the AffiliateWP Integration;
* Fixed: Typos;
* Fixed: Webhooks for successful payments and received payments not being triggered;
* Added: Network Admins can now create Coupons that apply to Setup Fees;
* Added: Pricing Tables now display prices after coupon code when a coupon URL is used;

Version 1.10.2 - 01/08/2019

* Fixed: Incompatibility with Beaver Builder Pro 2.2.4.2 and up;

Version 1.10.1 - 28/07/2019

* Fixed: JavaScript being thrown in some pages by wu-gutenberg-support.js;
* Fixed: Pricing Table shortcode now displays hidden plans when ids are passed;

Version 1.10.0 - 19/07/2019

* Fixed: Blank Broadcasts page in edge-case scenarios;
* Fixed: Issue with the customizer and domain mapping in some edge cases;
* Fixed: Incompatibilities with the Hummingbird cache plugin on WPMU DEV Hosting;
* Fixed: Check if a webhook call was already received from Stripe before re-adding it;
* Fixed: Template switching no longer overrides site title and admin email options;
* Fixed: Fixed issue with 100% off coupons for 1 cycle with PayPal;
* Fixed: Error on the text mode of the editor in the Broadcasts page;
* Fixed: Parent themes no longer appear when child themes are marked as available on plan settings;
* Fixed: [wu_templates_list] Shortcode breaking Elementor's edit screen; 
* Fixed: Connecting to Jetpack allowed the client to switch to any other installed theme, even the ones not allowed on the plan;
* Fixed: Brizy not working with sites with mapped domains;
* Improved: Added a filter (wu_gateway_paypal_max_failed_payments) for PayPal's max failed payments;
* Improved: The script that sends calls to count visits is a bit more clever now in order to reduce the impact on the server performance;
* Improved: WP Ultimo now logs wp_mail() failures for debugging purposes;
* Improved: Added plugins active on the main site to the system report as well;
* Improved: About page fetches the Changelog directly from the versions server now;
* Important - Improved: Security review performed using wpBullet (https://github.com/OWASP/wpBullet) on the codebase to patch possible security holes;
* Added: WP Ultimo now changes the default block editor "Generating Preview" window for a better white-labeled experience;

Version 1.9.13 - 20/06/2019

* Fixed: Template iframe SAMEORIGIN bug re-introduced on 1.9.12;

Version 1.9.12 - 09/06/2019

* Fixed: Bug where super admins were being removed from template sites on duplication;
* Improved: Adjustments to better support the Pro Sites Migrator;

Version 1.9.11 - 30/05/2019

* Fixed: WU_Logger not being able to log more than one line per request;
* Improved: Removed pagination from the Plan list table page and fixed the ordering on setups with large plan counts;
* Added: Filter wu_plan_get_quota added, allowing developers to change the plan quotas;
* Added: Compat functions to accommodate Networks coming from Pro Sites using the migrator;
* Added: Domain Mapping sync with WPMU DEV's managed hosting, with support to auto-SSL;

Version 1.9.10 - 29/03/2019

* Fixed: URL Preview now works for networks with the root directory on a sub-folder;
* Fixed: Adding new users to the main site even when that option is disabled;
* Fixed: Frontend access for extra sites being limited even when plan allows for unlimited sites;
* Fixed: Invoice generation error due to missing font file;
* Fixed: Forum RSS widget and changelog fetcher updated to pull from the new URLs;

Version 1.9.9 - 16/03/2019

* Fixed: Issue with expired subscription emails being sent days in advance;
* Fixed: Added support to Turkish on the PDF Invoices;
* Fixed: Subdomains not being correctly added to cPanel since 1.9.8;
* Fixed: Adding new sites via the admin panel was not using the site template attached to the plan;
* Fixed: Primary blog being reset everytime a new site was added from the admin panel;
* Fixed: Feature Plugins page not displaying any of the available feature plugins;
* Improved: Added a notice on the Network Admin Dashboard notifying super admins when the option to prevent search engine indexing is active on WP Ultimo settings;
* Added: Partial Turkish translation provided by Erdem Çilingiroğlu (Thanks!);

Version 1.9.8 - 27/02/2019

* Fixed: Switching templates cleans up the disk space limitation on the site;
* Fixed: Notice being thrown during the setup wizard flow;
* Fixed: Responsiveness of the template selector on the signup flow and on the Gutenberg block;
* Fixed: 502 errors when auto-login after registration is activated;
* Improved: WP Ultimo Forum widget is now loaded via ajax and displays the latest blog posts as well;

Version 1.9.7 - 28/01/2019

* Fixed: Submit button misalignment on Login and Registration screens;
* Improved: Adjustments to the styles to support WP Ultimo: Blocks;

Version 1.9.6 - 23/01/2019

* Fixed: Removed external call to fetch network IP address that was causing slow-downs;
* Fixed: Pricing Table grid styles not being loaded properly on the shortcode version;

Version 1.9.5 - 15/01/2019

* Fixed: Styles for the pricing table shortcode not being loaded on the front-end;
* Added: Full Brazilian Portuguese translation added, thanks to Fernando Arbex;

Version 1.9.4 - 15/01/2019

* Fixed: Minor misspellings across the plugin;
* Fixed: Declare iconv if it's not available on the current environment;
* Fixed: Allowing sub-domains to access the parent window on template previews. This fixes a bug with DIVI on the template previewer;
* Fixed: Adding Manual Payments was not working in some environments;
* Fixed: Prices entered using commas as decimal separator being flagged as invalid;
* Fixed: Forcing HTTPS on a site per site basis not working reliably;
* Improved: Changelogs on the About Page are now loaded via ajax;
* Improved: Russian Translations provided by @fumbic;
* Improved: Made necessary changes to support block versions (in the future) of our shortcodes;
* Added: Adding ?template-category at the end of the signup URL allows you to pre-select template categories on the Template selection step;

Version 1.9.3 - 15/12/2018

* Fixed: Add-on installer not working for free add-ons;
* Fixed: Small issue with PayPal and coupon codes;
* Improved: Performance improvements added to the code to reduce the number of queries;
* Improved: Added add-on download support for lifetime license holders;

Version 1.9.2 - 07/12/2018

* Improved: Moved inline forms from the admin_notices hook to the in_admin_header;
* Improved: Error Reporting now checks the error stack to determine if the issue is related to WP Ultimo or not;
* Improved: User counting functions now have a role exclusion list that can be expanded. By default, it excludes the WooCommerce 'customer' role from the totals;
* Improved: Removed WP Ultimo custom post types from the export screen of subsites;
* Fixed: Call to undefined function wo_die replaced with the right function name;
* Fixed: Activity Stream widget not displaying special HTML currency characters;
* Fixed: Improved compatibility with WP Admin Themes in general;
* Fixed: Broadcasts exposing network admin email. Now it only uses BCC and leaves the TO field blank;
* Fixed: Activity Stream not referencing dates using the timezone settings of the WordPress;
* Fixed: Template selection via query string now works with the new plan shareable URLs;
* Fixed: Small Incompatibilities with admin themes;
* Fixed: Incompatibility with Gutenberg on WordPress 5.0;
* Added: WP Ultimo now assigns posts, pages etc to the newly created user after signup;
* Added: Allow potential clients to search for templates on the Template Selection step;

Version 1.9.1 - 25/11/2018

* Fixed: WordPress auth modal showing up during the signup flow;
* Fixed: Some hosting providers add the SAMEORIGIN restriction to the template previewer, now we force the ALLOWALL header for template previews;
* Fixed: Frequency switcher on the pricing table shortcode not working on the front-end;
* Fixed: Setup Wizard not loading the necessary files and as a result, not working properly;
* Improved: Post count for limitations now takes Private posts as default and allow developers to change which post status should be considered;

Version 1.9.0 - 17/11/2018

* Fixed: Subdomains are now being sent to cPanel correctly;
* Fixed: Forcing HTTPS for subdomains for subsite assets;
* Fixed: Unable to unselect all Site Templates and save on plan edit page;
* Fixed: Removed the "Select this Template" bottom from the previewer on template switching contexts;
* Fixed: Fixed the shortcodes for the login URL on the "User Activated" (subsite_user_activated);
* Fixed: Prevent users from adding malicious domain names, like the network main domain, for example;
* Fixed: User should not be able to use the current site on the switch template action;
* Fixed: Issue with the site owner change form not updating due to object caching with Redis;
* Fixed: Added a new option to enable template overrides on plans;
* Fixed: Template previewer stuck on mobile view for some installations;
* Fixed: Template overrides now searches only on the main site as it is supposed to;
* Fixed: Remove image button on the Styling Tab was breaking the image preview;
* Fixed: Prefixed the .container class used on the plugin to avoid conflicts with other plugins;
* Fixed: WooCommerce Setup redirect taking precedence over the payment integration redirects;
* Fixed: Added some missing constants to the duplication code to prevent warnings;
* Fixed: Super Admins can always have access to the switching template button;
* Fixed: Auto-installer/auto-updaters not working due to change on the allowed URLs;
* Fixed: Checking the color settings for valid colors to avoid errors 500 on the template preview page;
* Improved: www. and *. are now also send to RunCloud.io;
* Improved: Added filter to prevent PayPal from loading the logo, or changing the logo URL;
* Improved: Added support to right-positioning of currency symbols on the pricing tables;
* Improved: Added a trim() call to the signup fields to clean the step fields;
* Improved: Saving mapped domain now checks if the desired domain is pointing towards the right IP Address before saving (patch sent by Anthony Vito);
* Improved: Added a filter to remove WP Ultimo logo changes on the login screen;
* Improved: Namespaced Stripe to avoid conflicts with other plugins that use the same libraries;
* Improved: WP Ultimo now uses an external service (and caches the results) to figure out the network public IP address;
* Improved: Replaced the admin pages to use the new WU_Page framework to improve performance;
* Added: Overlay message on Coupon management screen alerting super admins when they have coupon codes disabled;
* Added: "Contact Us" price option for plans;
* Added: Ability to duplicate plans;
* Added: New option to add an external URL for the Terms of Service page;
* Added: New format for shareable links for plans, using the plan slug;
* Added: Feature Plugins page for easy install of feature plugins;
* Added: WP Ultimo now has a error reporting feature that allows super admins to choose if they want to send anonymous error data to the developers;

Version 1.8.2 - 01/10/2018

* Fixed: (Again) Users can now select their own sites to be used as templates when creating new sites; This option was not available for some reason;
* Fixed: Responsiveness of the template previewer on the iPhone X, once and for all;
* Fixed: Bug with domain mapping redirecting to the original domain in some edge-cases;
* Fixed: Invalid trial days settings breaking the signup process;
* Fixed: Search and Replace API now supports Beaver Builder and other plugins that rely on deep serialization;
* Fixed: Forcing Site Icon to display on the registration page of WP Ultimo;
* Improved: Cleaned system info from WP Ultimo API data;
* Improved: Domain Mapping rewriting of URLs for assets now support Autoptimize (plugin);
* Improved: Gutenberg Support for domain mapping;

Version 1.8.1 - 11/09/2018

* Fixed: Users can now select their own sites to be used as templates when creating new sites; This option was not available for some reason;

Version 1.8.0 - 10/09/2018

* Fixed: Responsiveness of the template previewer on the iPhone X;
* Fixed: Beta updates not being displayed for add-ons;
* Fixed: Template_id on the URL now works if skip_plan is also present;
* Fixed: Added a better error message to put on the place of "Something wrong happened in the duplication process";
* Improved: Shortcode [wu_templates_list] now can take a list of template ids to display;
* Improved: New about page with useful info and links;
* Improved: Add-on plugin updater;
* Improved: Replaced the update URLs;
* Refactor: Moved cleaning settings functions out from the core class;
* Refactor: Moved stats calculation functions out from the core class;
* Added: Users can now select their own sites to be used as templates when creating new sites;
* Added: Export & Import added for WP Ultimo Settings, Plans, Coupons and Broadcasts;
* Added: Our CPanel integration now also syncs subdomains, to allow support for autoSSL;
* Added: ServerPilot.io domain mapping and subdomain sync support added. Also supports autoSSL;
* Added: Custom CSS control on the customizer for the WP Ultimo sign-up and login page;
* Added: Integration with Zapier no longer needs manual setup! You can simply search for the WP Ultimo app on their app list! The tutorial at https://docs.wpultimo.com/knowledge-base/integrating-wp-ultimo-with-zapier-using-webhooks/ was updated to reflect this change;

Version 1.7.3 - 13/08/2018

* Fixed: When wp-login-php obfuscation is active, the user was getting the 404 error page on logout;
* Fixed: wp-login.php Obfuscation was not fully supporting password reset;
* Fixed: PayPal gateway starting output without ob_start() on the PayPal button code;
* Fixed: Scoped chosen.js styles to prevent conflicts with other plugins, like Give;
* Fixed: Graphs of the statistics page are now working again;
* Fixed: Link "View on Stripe Dashboard" was always redirecting to the sandbox version of the Stripe Dashboard;
* Fixed: Stripe not formating the values of coupons correctly when using absolute values;
* Fixed: Second level serialized array not being picked up on Search and Replace;
* Fixed: Some scripts and images not using the mapped domain when a mapping is activated;
* Fixed: Responsiveness of the template previewer on mobile screens;
* Fixed: Login after signup is now fixed on WP Engine (finally!);
* Improved: Updated the "Translate WP Ultimo" link;
* Improved: Removed the "remove site" link when the user has only one active site;
* Improved: Make sure we display the "Add new site" link to users without sites, even if the "Enable Multiple Sites" option is disabled;
* Improved: Allow admins to filter the min length of passwords on signup (wu_password_min_length);
* Improved: Super Admins can now choose if they want to have visits limitation and counting or not;
* Improved: Alert messages asking for confirmation when super admin clicks the Delete button on the Plans list or inside a plan edit page;
* Improved: Alert message asking for confirmation added to the cancel payment integration link on the Account page;
* Improved: The webhooks menu is now a first level menu;
* Improved: License Key fields are now password fields;
* Improved: Super-admins can add the license code to the wp-config.php file to activate the plugin, using define('WP_ULTIMO_LICENSE_KEY', 'KEY_HERE');
* Added: Super admins can now block the frontend of sites above the quota number on Network Settings - this is useful when a client downgrades to a plan with lower site quota;
* Added: Option to add coupon codes to subscriptions on the management screen;
* Added: New email template - Super Admins now get an email when a subscription becomes inactive, allowing them to take action, if necessary;
* Added: New email template - Super Admins now get an email when a payment atempt fails, allowing them to take action, if necessary;
* Added: Signup URLs can now contain a template_id parameter to auto-set the template and skip the template selection step;
* Added: Closte.com domain syncing support with no extra configuration steps necessary. It also includes AutoSSL!

Version 1.7.2 - 20/06/2018

* Fixed: WU_Signup()->get_transient() now switches to main blog before retrieving the data, to guarantee we are searching in the right database table;
* Fixed: The "move posts to trash or draft on downgrade" feature now handles unlimited post quotas correctly;
* Fixed: The URL previewer on sign-up, uses the right domain if the admin offers a first option different from the main network domain;
* Improved: Added a simple check for the PHP version, to make sure the user is running 5.6 or later instead of simply throwing a fatal error;

Version 1.7.1 - 17/06/2018

* Fixed: Critical issue causing the login rename feature to replace the main site with the login form;

Version 1.7.0 - 16/06/2018

* Fixed: Some wrong time formats being used on the codebase causing Stripe, PayPal and Ultimo Subscriptions to get 12h out of sync sometimes;
* Fixed: Only register subscription status widgets when the user has a valid plan;
* Fixed: Limits and Quotas widget not being displayed to super admins when visiting a sub-site;
* Fixed: Export CSV with missing columns and containing coupon code info;
* Fixed: Email alerting admin email change being sent on template duplication;
* Fixed: Small incompatibility with the DIVI builder on the post edit page;
* Improved: Plan filter options on the subscription list page;
* Improved: We no longer display a success message when submitting the map domain form with no domain address;
* Improved: Added an "Account page" link on the Payment Integration screen;
* Improved: Added an option to customize the Payment Integration screen title;
* Improved: Added an option to customize the Payment Integration screen description;
* Improved: Added options to customize the gateway integration button labels;
* Improved: Changed the signup-main template file, adding a new wrapper div to allow the body tag to take the whole screen, adding support to background images via CSS;
* Improved: WP Ultimo sign-up flow now supports styles coming from Material WP;
* Improved: Revised the account delete flow;
* Improved: Added a filter to the site count calculator for subscriptions;
* Improved: Visit counter now uses a ajax script to count visits to avoid problems with caching plugins - caching plugins supported: W3TC, WP Super Cache, WPEngine Cache and WPFastestCache;
* Improved: Admins can reset visit count on the sub-sites' limits and quotas widget;
* Improved: New email templates added for site removal and account removal (admin and user);
* Improved: Added an option to set a different logo for the login and sign-up pages;
* Improved: The CPanel integration now supports WU_CPANEL_ROOT_DIR, allowing admins to change the root directory of the add-on domains (defaults to /public_html). The CPanel tutorial was updated to reflect that change;
* Improved: Added a handful of new filters to important parts of the wu-subscription model, allowing for greater extendability;
* Improved: We now only display the disk space limit on the Account Statistics widget (client dashboard) when the disk space check is turned on in the Network Admin -> Settings -> Network Settings page;
* Improved: Replaced the phpInvoicer version with the latest one to avoid old PHP deprecated notices;
* Improved: Re-arranged some of the settings on WP Ultimo -> Network Settings to keep things more organized and easy to find;
* Improved: Sites lists now have easy link to reset visits count;
* Added: Beta Program, allowing network admins to receive notification of alpha, beta and release-candidate updates;
* Added: Super Admins can now allow users to choose from multiple domains for their site during the sign-up process;
* Added: Network admins can hide sub-site admin widgets with a filter (read more: https://docs.wpultimo.com/knowledge-base/removing-the-wp-ultimo-widgets-from-the-clients-dashboard/);
* Added: Network admins can now change the login URL from wp-login.php to something else; It also supports obfuscation of the old wp-login.php URL, preventing brute-force attacks;
* Added: Super admins can now set a grace period before finally locking down the users' frontend;
* Added: Super Admins can now set the "copy media" option on a per plan basis, under the advanced options portion of the Plan edit page;
* Added: Super Admins now can set the extra users plan quota to unlimited as well;
* Added: RunCloud.io support! Domains get automagically added to the RunCloud web-app domains panel after mapping;
* Added: Two new alert emails for monthly visits limits (limit approaching and limit reached) added;
* Added: Network admins can decide how to handle posts types above quota (do nothing, move to trash or mark as drafts);
* Added: Search and Replace UI on WP Ultimo Settings -> Network Options;
* Added: Support to setup fees on plans; 
* Added: Support to single charges (Stripe and Manual); 
* Added: The current card being used for payment is displayed on the Accounts page and Subscription management screen, if using Stripe;
* Added: Admins can now cancel the payment integration directly from the Subscription Management screen;
* Added: Clients can now updated their card info on the Accounts page, if using Stripe;


Version 1.6.2 - 03/05/2018

* Fixed: Small spelling mistakes on the dashboard;
* Fixed: Broadcasts widgets take the whole screen now on larger screens;
* Fixed: Link on the bottom of the HTML email template having a /1, which causes the link to be broken;
* Fixed: Making sure we don't show the "Use this Template" outside a sign-up context;
* Fixed: Small activation error on the site-hooks file;
* Fixed: Small bug on the Jumper builder, causing broken links;
* Fixed: Error copying AffiliateWP tables over from template sites halting the sign-up process;
* Fixed: Super Admin being removed from the main site when creating a new site;
* Improved: All strings of the PDF invoices are now translatable;
* Improved: Added an option to filter the wp_die title;
* Improved: We now check post limits when trying to restore posts to avoid users trashing and restoring posts to bypass plan limitations;
* Improved: Invite and Account Activation emails are now sent using our email template;
* Added: Experimental Search and Replace filter added;
* Added: CPanel support! Domains get automagically added to the CPanel Add-on Domains panel after mapping;


Version 1.6.1 - 23/04/2018  

* Fixed: "Webhook Settings" link on the Webhooks page redirecting to the wrong WP Ultimo settings tab;
* Fixed: Saving webhooks giving javascript error on Safari;
* Fixed: Cloudways giving 502 when a new domain was mapped;
* Improved: Visits line on the "Limits and Quotas" widget now displayed the next reset date;
* Improved: Added an extra setting for Cloudways, allowing admins to send extra domains alongside the mapped ones;
* Improved: The Cloudways integration now let admins sync extra domains. The tutorial on https://docs.wpultimo.com/knowledge-base/configuring-automatic-domain-syncing-with-cloudways/ was changed to reflect this extra option;


Version 1.6.0 - 21/04/2018

* Fixed: "Select Template" showing up in the site template previewer when in shortcode context;
* Fixed: Template Previewer is handling SSL for subdomains in the right way now;
* Fixed: Template Previewer taking into account the plan selected previously, to display only the templates available for that selected plan;
* Fixed: "www." appearing on the site-url previewer;
* Fixed: Incompatibility with WP All Import WooCommerce Add-on;
* Fixed: Small portion of the bottom of the site preview being cut off on the Previewer screen;
* Fixed: Users with Subscriptions created with no sites can now create sites from the panel without getting a "no permissions" error;
* Fixed: Missing variable on domain mapping causing redirect errors in edge cases;
* Fixed: Auto-login after registration not working in some environments;
* Fixed: Invoice generator not being able to handle filenames with special characters and throwing errors;
* Improved: Re-phrased the Sunrise.php check step on the Setup Wizard to make clear that users must add the define('SUNRISE', true); line above the /* That's all, stop editing! Happy blogging. */;
* Improved: Added extra check on subscriptions to make sure free subscriptions are not getting expiring & expired notification emails;
* Improved: Reorganized the broadcasts screen to add a preview block and to clean up the send form;
* Improved: Added a login link on the "Site is not available" screen for admins to log in when "Block Front-end Access" is enabled;
* Improved: Replaced the old "No Preview" image with something less intrusive;
* Improved: Re-adjust the aspect ratio (320x179) of the template preview blocks to match the new resolution returned by the screenshot scraper;
* Improved: Adopted a more aggressive minification strategy for WP Ultimo scripts, boosting performance and reducing file sizes up to 50%;
* Improved: Making sure dbDelta does not dirty the logs with database errors on activation;
* Improved: Added a filter to allow developers to add custom meta to sites after site creation on the signup;
* Added: Option to prevent search engines from indexing template sites;
* Added: New feature allowing users to switch templates after sign-up;
* Added: New feature allowing admins to add a visit limit for plans;
* Added: New feature allowing admins to overwrite the trial settings on the plan edit page;
* Added: Different listing option on the Network Admin -> Sites page to list only Template sites;
* Added: Webhooks! Support for 8 events (Account Created, Account Deleted, Payment Received, Successful Payment, Failed Payment, Refund Issued, Plan Changes, New Domain Mapping), more will be added as we go;
* Added: WP Engine support! Domains get automagically added to the WP Engine panel after mapping;
* Added: Cloudways support! Domains get automagically added to the Cloudways panel after mapping;
* Added: Jumper UI that allows admin to easily switch between admin pages;


Version 1.5.5 - 07/03/2018

* Release Notes: http://bit.ly/wp-ultimo-1-7-0
* Fixed: Change plan was not triggering role change and meta update of plan_id on the user;
* Fixed: We now force avatar display on our pages even when that option is disabled on the main site settings;
* Fixed: Customizer not loading preview in when a mapped domain is active;
* Fixed: Domain Mapping box on User Account page now gets the right URL for CNAME for Multisite installs running inside a directory (like yourdomainname.com/wp, for example);
* Fixed: Subscription being marked as "trialing" wrongfully because weak type checking of the trial days attribute;
* Fixed: When a site template was no longer available, a "false" option would be displayed in the template selection screens during sign-up;
* Fixed: Fixed on the double animation effect on the princing table frequency selector;
* Important: We now use the locale of the user to render the Stripe box - Support languages: zh, da, nl, en, fi, fr, de, it, ja, no, es, sv;
* Improved: Changing the trial now updates the active until, to make sure we correctly categorize subscriptions as "on-hold";
* Improved: The quotas widget is now always hidden to the subscriber role;
* Added: Coupons now support limitations by plan and billing frequencies;
* Added: Top-bar added on the template preview page;
* Added: Notification Emails added for expiring and expired subscriptions;
* Added: Notification Emails added for expiring and expired trial periods;
* Added: Option to Activate and Deactivate plugins for plans directly from the Plugin Screen in the network admin;


Version 1.5.4 - 11/02/2018

* Fixed: Active until "remaining" string on the subscription was returning trial values instead of the right value;
* Fixed: "Block Frontend Access" option was not working;
* Fixed: Added the 'key' keyword to the sensitive info filter to prevent info like the MailChimp key from leaking into the frontend settings array;
* Fixed: Added extra checks to the invoice generation process;
* Fixed: Edge-case where broadcasts messages where being displayed to non-targeted users;
* Fixed: Subscriptions created by the panel now use the default role set in the Settings, which is also filterable;
* Improved: Email Blocks on the Settings -> Emails now is displayed with a lower opacity if that email is disabled;
* Improved: Post Type limits now handle singular and plural limits. "1 Post" and "10 Posts", for example;
* Improved: "/mo", "yoursite" string present on pricing table added as a translatable string;
* Improved: Subscriptions list table now displays Gravatars to make things a bit more personal and colorful =);
* Improved: Replaced jQueryUI DateTimePicker with FlatPickr;
* Added: Support for modifying subscription dates by the hour and minute, to allow further control;
* Added: Server time clock on the top bar to help admins when managing subscriptions;
* Added: Activity Stream widget on the Network Dashboard;
* Added: Notification email to let the network admin know when a user mapped a new domain to his site (thanks, Simon, for the suggestion);
* Added: Added plan and billing frequency fields on the Subscription -> Add New page;
* Added: New engine to install setting defaults without evoking loops;
* Added: Post Type Block: Super admins can block specific post types based on the user's subscription plan;
* Added: Role per plan: Super admins can now select a role to be used when the user signs up with a particular plan;
* Added: Template Options per plan: Super admins can now filter which templates will be available in the template selection step based on the plan selected;


Version 1.5.3 - 02/02/2018

* Fixed: Reverted admin options code to 1.5.0, since some users are still reporting CPU and Memory spikes;
* Fixed: Removed source-maps from final build to avoid 404 appearing on the console;


Version 1.5.2 - 31/01/2018

* Fixed: CPU and Memory spike due to the new default setting getter function going into an infinity loop in certain cases;
* Fixed: Subscription save now clears the sites object cache to fix incompatibility with Redis - thanks Jorge, for the report;


Version 1.5.1 - 24/01/2018

* Fixed: Shortcode wu_plan_link now treats the results to avoid issues;
* Fixed: Space quota updating when the plan is changed from the subscription management screen as well;
* Fixed: Add New Users from the sub-sites were not sending invitation email;
* Fixed: Small fix on the dutch translation file that was causing the Terms link to be broken;
* Fixed: Get allowed themes for individual sites now return an empty array by default to prevent warnings;
* Fixed: Subscriptions created via the Add New admin menu were not being saved;
* Fixed: WU_Signup not working on sites running on sub-folders;
* Fixed: Country selector not working as intended;
* Fixed: Front-end blocking does not prevent the Payment Screen from showing up;
* Improved: Filter to remove the H1 from the template selection step (wu_step_template_display_header);
* Improved: Explicit mention that WP Ultimo is under GPLv2 on both plugin and readme headers;
* Improved: Sunrise.php now will continue to work if the admin moves the plugins folder to a different location;
* Improved: Sunrise.php is now versioned and will display a message when a new version is received, with automatic replacing with just one click;
* Improved: Replaced the engine generating the .pot file with the same one being used by WordPress Core;
* Improved: Plans List on the Change Plan widget are now displayed horizontally to make sure they all fit (useful for networks with a lot of plans);
* Added: Top-bar navigation when visiting templates;
* Added: French translation, thanks to @nouvelletechno;
* Added: Spanish translation, thanks to Matias Candia;
* Added: Alert message being displayed to the user asking for confirmation when he/her tries to add a custom domain;


Version 1.5.0 - 04/11/2017

* Fixed: Manual Gateway handling upgrades and downgrades;
* Fixed: Change plan sometimes not updating the disk space quota of blogs;
* Fixed: Added edge case to domain mapping and the get_scheme function, to allow more flexibility to sub-domains installs using mapped domains;
* Fixed: Manual Gateway now checks if it should send the invoice on integration;
* Fixed: Incompatibility with Sitemap generators (thanks, Aron Prins =D);
* Fixed: Now WP Ultimo adds the original URL to the allowed cross-origin access array, to further prevent CORS issues;
* Fixed: Issue with sign-up redirects not being exact;
* Fixed: Relative time string on the Subscription page now support time spans larger than one year;
* Fixed: When the copy media option is disabled, we now remove the attachment posts related to each media file;
* Fixed: Small issue with creating new sites in some specific database environments;
* Fixed: Setup wizard link on the Mercator error message was broken;
* Fixed: We now check if the Stripe API was already loaded, to prevent fatal errors;
* Fixed: User creation now is timezone-sensitive, preventing delays in invoices and payments;
* Fixed: Spacing of integration buttons fixed;
* Improved: Multi-select fields on the Settings now can be sorted. That allow users to change the order of the allowed templates, for example;
* Improved: New option 'display_force' added to password fields. If checked, it adds a strength checker below the password field. More information: https://docs.wpultimo.com/knowledge-base/adding-a-strength-meter-to-password-fields/ 
* Improved: Form Fields helper function now gives the option to change the default value of the billing frequency field;
* Improved: Applied number_format to statistics widgets;
* Improved: "Instructions to Pay" field on Manual Gateway now supports is now a WP Editor field;
* Improved: Updated the Dutch language files - Thanks, Aron Prins;
* Improved: Moved the Domain Mapping and SSL settings to their own Settings Tab;
* Improved: SSL Settings Summary widget on the Settings page to help visualizing the SSL configuration;
* Added: Geo-location block added;
* Added: Restricted Content shortcode for plans and default content; More information: https://docs.wpultimo.com/knowledge-base/shortcodes/ 
* Added: New shortcode with the front-end URL of a user's site for the create account template email (WP Ultimo -> Emails);
* Added: Option to enable/disable SSO with enabled as the default behavior;
* Added: Manual Gateway now displays link to a modal containing Instructions to Pay in the pending payment row in the Billing History widget;
* Added: Table Updater checker added, to make sure custom tables are always in sync with their latest version;


Version 1.4.3 - 31/08/2017

* Fixed: Fatal errors on activation on single installs;
* Fixed: Welcome email not being sent after user sign-up;
* Fixed: Shortcode plan_link using echo, should use return;
* Improved: Removed plan_id and plan_freq from the filtering function, and now they get saved as meta on the user;
* Improved: Minor changes to the Manual Gateway to avoid conflicts with the WooCommerce Integration Add-on and all future gateways supporting the 'on-hold' status;
* Improved: Added the WP Ultimo as top-level menu on the admin bar;
* Improved: Sign-up customization steps only show in the main site now;
* Added: Essential changes to allow the WooCommerce Integration to work;
* Added: Filter on the metadata array sent to Stripe, to allow admins to add info they want to send to Stripe;
* Added: Filters to allow better customization of invoices sent: wu_invoice_from, wu_invoice_to, wu_invoice_bottom_message;
* Added: Reset Password now uses the same WP Ultimo template, even when WooCommerce is activated on the main site;


Version 1.4.2 - 18/08/2017

* Fixed: Redirect error using the wu_plan_link shortcode;
* Fixed: Customizer bug onload;
* Fixed: Coupon on wu_plan_link;
* Fixed: Plan save requiring monthly pricing even if that billing option is not available;
* Fixed: Added the 'manage_network' capability as a requirement to access all WP Ultimo Registration settings on the customizer;
* Improved: Replaced the approach of the site creation engine from the admin panel with the new one introduced on 1.4.0;
* Improved: Replaced the support email with the new one support@wpultimo.com;
* Added: Network admins now can add new payments manually directly from the subscription management page;
* Added: New option to duplicate templates on the Network Sites list;


Version 1.4.1 - 10/08/2017

* Fixed: Change Plans not displaying pricing tables;
* Fixed: Pricing table shortcode was landing on the plan step again after a plan was selected;
* Fixed: Removed logs folder check from the setup wizard;
* Added: Option to toggle the cron-job of the Screenshot Scraper;


Version 1.4.0 - 08/08/2017

* Release Notes: http://bit.ly/wp-ultimo-1-4-0
* Added: Option to copy Coupon's shareable links on the All Plans and Edit Plan pages;
* Added: Templates overriding for sign-up and emails;
* Added: Helper function to create users with subscriptions: wu_create_user();
* Added: Helper function to create a new WP Ultimo network site: wu_create_site();
* Added: Fields and Steps API to the Sign-up flow;
* Added: Helper function to add new sign-up steps: wu_add_signup_step();
* Added: Helper function to add new sign-up fields: wu_add_signup_field();
* Added: Deleting users and sites now removes the mappings associated with those sites;
* Added: Option on the Email Settings to allow plain emails to be sent;
* Added: Logs are now downloadable and delectable;
* Added: Option to hide the Plan Selection step if there is just one plan available on the platform;
* Added: Three different behaviors added to Coupon Code: disabled entirely, only via URL code or via URL code and Sign-up field;
* Added: New shortcode wu_user_meta to retrieve user meta information on the front-end. Useful for retrieving information collected during sign-up using custom fields;
* Improved: Shortcode wu_plan_link updated to work with the new Sign-up codebase;
* Improved: WP Ultimo now checks for empty user role options to rebuild them if needed;
* Improved: WP Ultimo now only displays broadcasts published after a user on the target group registered. This prevents new users from being bombarded by all the previously published Broadcasts;
* Improved: Better template selection field;
* Improved: Refactorization of the Screenshot Scraper: it now can handle up to ~80 without crashing;
* Fixed: WU_Colors being added more than once;
* Fixed: Coupon validation functions was not checking for post type, only for post titles;
* Fixed: Fixed some broken elements on the Coupon Codes list table;
* Fixed: Links breaking on Broadcasted emails;
* Fixed: Missing variable declarations inside template_list and pricing_table shortcodes;
* Fixed: Broadcast emails now supports multiple lines;


Version 1.3.3 - 19/07/2017

* Fixed: Issue with logs folder being located inside the plugin directory and, as a consequence, being wiped out on every update;
* Fixed: Non-numeric errors being thrown on the Account page when Quarterly and Yearly billing frequencies were disabled on PHP 7.1;
* Fixed: Error on the redirect URL filter after integration;
* Fixed: Manual Gateway applying coupon codes;
* Added: Log viewer added to the System Info page as a separate tab;
* Added: CSV Exporter for Subscriptions on the Subscriptions Page;
* Added: Option to select which Quota options will be displayed on the quota widget of the users' dashboard on WP Ultimo Settings -> General Tab;


Version 1.3.2 - 14/07/2017

* Fixed: Changed the order of Stripes Secret and Publishable keys on the settings to match the order on the Stripe Dashboard, avoiding confusion;
* Fixed: Site_Hooks::get_available_templates() now returns sites without a valid owner as well;
* Fixed: Default role not being applied to new sites created from the dashboard;
* Fixed: Stripe Gateway now communicates the discount applied to the subscription on the modal window for payment;
* Fixed: Template creation now handles a few edge cases preventing those sites from appearing on the available templates list;
* Critical Fixed: Site creation from admin always being created for the super admin;
* Improved: Added a new option to change the default behavior of plugin uninstall: now the default state is to not wipe plugin data;
* Improved: Added current plan to the change plan hook;
* Improved: Remove Terms of Service contents from System Info page;


Version 1.3.1 - 05/07/2017

* Fixed: Variable dump on sign up preventing redirect to the admin panel;
* Fixed: Reverted the duplication script to the default tables only;


Version 1.3.0 - 03/07/2017

* Release Notes: http://bit.ly/wp-ultimo-1-3-1
* Fixed: Small issues with the template selection screen;
* Fixed: Email content editor saving URLs in a weird way, adding slashes;
* Fixed: Site Template description not being saved on the "Edit Template Info" screen;
* Fixed: Variable Dump including sensitive information;
* Fixed: Plan re-ordering not working on different languages;
* Fixed: WP Ultimo icon on multi-network;
* Fixed: Terms of Service field adding extra "\" after quotes;
* Fixed: Error on creating new sites from the admin panel with a plan and template;
* Improved: Uploads Quota can now be set as hidden on the plan pricing tables;
* Improved: Prefixed SweetAlert to avoid conflicts with other plugins;
* Improved: Replaced the content field for email templates with WP Editor;
* Improved: Added an ID to each plan HTML markup on the pricing tables;
* Improved: Added an extra check to the domain mapping (now called System) step on the Setup Wizard, to check if the right version of sunrise.php was loaded;
* Improved: Added an extra check to the System step on the Setup Wizard to make sure the logs directory exists and is writable;
* Improved: The features list field now uses a WP Editor widget, to allow the addition of bold text, italic, and links without editing HTML code, the same applies to the Broadcast message field;
* Improved: Add-ons page now handles installs for free add-ons;
* Improved: Duplicator now copies extra tables as well;
* Improved: Added hooks for adding extra elements to the Advanced Options on the edit plan and coupon pages;
* Added: wu_templates_list Shortcode added to display all the available templates on the front-end;
* Added: Warning message on the System Info tab if the logs directory is not properly configured to be writable;
* Added: Filters to change the labels and tooltip texts on the signup fields;
* Added: Option to enable or disable copying media files from the template sites;
* Added: Support to Multi-Network Environment (beta);
* Added: Readded the SSO script for single sign-on login;
* Added: Widget for Forum Discussions on the Network Admin;


Version 1.2.1 - 26/05/2017

* Fixed: Template categories sometimes appearing two times;
* Fixed: Not being able to remove items from pricing tables for plans;
* Fixed: Pagination on Subscriptions page take status into consideration now;
* Fixed: Weird redirects when enabling domain mapping;
* Fixed: Mercator error message being displayed even when the enable domain mapping option was deactivated;
* Fixed: WordPress Overwrite options on WP Ultimo Settings - Network not saving properly;
* Fixed: Prefixed the bootstrap classes we use;
* Fixed: Problem with template selection with resolutions under 780px and console messages;
* Fixed: Removed the payment step due to login inconsistency across different environment;
* Improved: Added a new style to the WP Error page to make it more consistent with the rest of the plugin, including the "Payment Required Needed";
* Improved: Switched screenshot scraper from file_get_contents to cURL for a 50% performance improvement;
* Improved: Added a more robust capabilities system to limit access to the Account page;
* Improved: Custom domain meta box now gives an error if the user tries to input a domain already being used in the network;
* Improved: Domain Mapping now handles srcset in images as well, to prevent CORS errors;
* Added: Shortcode wu_plan_link added to be used in custom pricing tables, use with plan_id and plan_freq;
* Added: Template selector turns into a select-box on resolutions under 780px;
* Added: Admins can search subscriptions on the subscriptions List Table - with ajax;
* Added: Terms of Service now can be set using a full-fledged editor, with prettier exhibition on the front-end;
* Issues: Removed SSO temporarily in order to prevent bugs from redirect loops until we can find a solution to the issues we are having with it.


Version 1.2.0 - 12/05/2017

* Fixed: Small typos on the settings page and other places;
* Fixed: Some string were missing i18n on the subscription management screen;
* Improved: Add-ons page now supports filtering as well;
* Improved: Security on issuing refunds;
* Added: Support for zero decimal currencies in Stripe;
* Added: Site Template Categories with filtering on the Signup, and a more consistent UI for editing site templates on the backend;
* Added: Option to hide plans from the pricing tables;
* Added: 100% OFF coupon codes with unlimited cycles don't ask for payment integration any longer;
* Added: Users can now add new sites from their panels, and admins can limit the number of sites on each plan.
* Added: Sites list added to the Subscription Management (admin) and My Account (user) Pages;
* Added: Custom capability 'manage_wu_account' created to control access to the Account page;
* Added: Network Admins can now set a different role for the users created via the signup process (defaults to admin);
* Added: Network Admins can now create subscriptions for users created outside of the signup flow (useful for migrating existing users);
* Added: Network Admins can now remove subscriptions from the Subscriptions list;
* Added: Payment is now a Signup Step when there is no trial;
* Added: Subscription is no longer deleted when a site is;
* Added: Option to send invoices when a payment is received from the gateways;
* Added: Manual Payments Gateway (beta);
* Removed: Old UI on site settings to add thumbnail;


Version 1.1.5 - 24/04/2017

* Fixed: Remote calls for activation API returning non-numerical values;
* Fixed: Formatting errors on Subscription values Gateways;
* Fixed: Small issues with resetting passwords;
* Fixed: Network active plugins do not show up on the subsites plugins page anymore;
* Fixed: Some small issues when creating sites from the panel using a plan;
* Improved: Added the all_plugins filter to the Plans advanced settings;
* Improved: Settings API now let us pass default values to the get_setting function;
* Improved: Settings API now allows add-ons to save their defaults on plugin activation;
* Improved: Core settings of WP Ultimo are now displayed on the System Info page as well;
* Improved: Image fields in the Settings now supports the removal of a previously uploaded image;
* Improved: WP Ultimo now saves the defaults when new options are added to it;
* Added: Link to go back to previous steps added to sign up flow;
* Added: Admins can now disable monthly billing if they wish to do so;
* Added: Admins can highlight a different billing frequency by default (eg. You have monthly, quarterly and yearly billing enabled, but you want the yearly to be highlighted by default);
* Added: Broadcast system: admins can now post admin notices to sites network-wide, targeting by plan and user; they can also send emails using the same targeting system;


Version 1.1.4 - 07/04/2017

* Fixed: Subscriptions with price set to zero being locked anyways;
* Fixed: Small compatibility issue with Live Composer Page Builder;
* Fixed: Stripe behaving strangely with different decimal places configurations;
* Improved: Activation now only checks the license code remotely, not locally;
* Improved: Pricing tables now display an error message for logged users before redirecting them back to the home page;
* Added: Partial Vietnamese Translation (Thanks to Richard Tuan);
* Added: Now users can overwrite the list of allowed themes by enabling themes for each site individually;
* Added: Settings and Documentation links added to the plugins table on the network admin;
* Added: Add-on page now let users buy add-ons, also let those with pre-launch licenses install all available add-ons directly from the admin;


Version 1.1.3 - 31/03/2017

* Fixed: Adding sites from the admin panel not working;
* Fixed: JQuery UI Styles using HTTPS - Thanks, Richard;
* Fixed: Adding new users throwing a fatal because of a redirect in the PayPal gateway not being checked;
* Fixed: Ajax tables throwing order and orderby undefined indexes notice;
* Fixed: Delete links not clickable throughout the WordPress admin;
* Fixed: Validating URL that users enter as custom domain;
* Improved: Subscriptions now get deleted after the user is deleted;
* Improved: Removing unnecessary files;
* Improved: The template selection list now displays the site name, not its path (which did not work for subdomain installs);
* Added: Filter to change the redirect URL after login (tutorial: https://docs.wpultimo.com/docs/changing-the-redirect-url-after-sign-up/);
* Added: Admins now have the option to hide specific post types from the pricing tables;
* Added: The network IP setting now displays the apparent IP address of the network by default;
* Added: Option to remove invalid subscriptions (subscriptions without user);
* Added: System Info page, to allow for faster debug and troubleshooting with support requests;
* Added: Shortcut buttons to extend and remove time from a subscription, via ajax;


Version 1.1.2 - 24/03/2017

* Fixed: Prevented the creation of tables on activation when they already exist;
* Added: Hooks on the gateway payment notifications to allow integrations;
* Fixed: Subscription creation adding an extra day to the active_until parameter of a subscription;
* Added: Manage Subscriptions link to the topbar WP Ultimo Menu;
* Fixed: Toggle of metaboxes in our admin pages now work properly;
* Fixed: Plan upgrade not working when upgrading from a free plan;
* Fixed: Language files not being loaded;
* Fixed: Free subscription being displayed as inactive in the subscription panel;
* Fixed: Small spelling errors;
* Added: Partial translation to Brazilian Portuguese (Signup Flow only);
* Improvement: Requirements engine in our Settings API now supports multiple requirements;
* Fixed: Some issues with the creation of sites from the admin panel;
* Added: PayPal Subscription button added as an option on the PayPal Gateway;


Version 1.1.1

* Fixed: Plan limits not working when the value set is zero;
* Fixed: Logo on the signup flow redirecting to wpultimo.com instead of home of the current network;
* Fixed: Duplication on the "Add Sites" menu only occurred when the attributed user is a new user;


Version 1.1.0

* Added: Signup flow is now responsive;
* Added: New subscription management screens;
* Added: Template site's thumbnails can be manually set;
* Added: Template site's thumbnail generator can be manually trigger from the settings page;
* Added: Coupon codes working with both payment gateways;
* Added: Refunds, both partial and full, supported by both payment gateways;
* Added: Styling Tab with logo and color options;
* Fixed: Blocking frontend access when subscriptions are inactive;
* Fixed: wp-signup.php now works even when accessed without the ".php";
* Fixed: sunrise.php now checks for Mercator before requiring it. Requires the placement of the new sunrise.php file in the wp-content directory;


Version 1.0.5

* Fixed: Now mapped domains are applied to media items inside posts and pages;
* Fixed: Blocking of sites after subscription is over is now done using native WordPress function;


Version 1.0.4

* Enhanced: Stripe gateway now prepopulates the email field with the customer email;
* Fixed: Free plan does not display the trial line in the pricing tables anymore;
* Fixed: Encoding problem with tooltips in some parts of the code;
* Fixed: Problem with is_active() giving false positives solved;
* Fixed: Template selection in "New Site" screen giving PHP errors - reported by Darjan;
* Fixed: Validation error with queterly and yearly plan prices when editing/adding a plan;
* Added: Option to block frontend access to site after subscription is over;
* Added: Terms of service agreement field in signup flow, with option to enable or disable it in Network Options;
* Added: Clarification note on the Activation tab to let users know that activating your copy does not make the plugin auto-update. It only displays an update notice and allows you to update the plugin in the same fashion as wordpress.org plugins, directly from the admin panel;
* Under-the-hood: Our settings API now supports option dependencies;


Version 1.0.3

* Fixed: WP Signup steps breaking with WordPress 4.7;


Version 1.0.2

* Fixed: Shortcode for pricing tables always being displayed at the top of the page, ignoring the actual position of the shortcode;
* Added: Plans now have a new advanced tab in their edit page allowing the addition of new custom feature descriptions to their pricing table;
* Added: Button on the template select screen to let the users see each template site;
* Improved: Under the hood improvements in code and performance;


Version 1.0.1

* Enhanced: Auto-updater now works and was improved security-wise, checking for refunded license_keys;
* Fixed: Incompatibilities with PHP 7 (throeing fatals on activation) were solved;
* Added: Option in the Network edit site options panel to add a site owner to a site or to transfer a site to a specific user;
* Added: Option to change the plan of a user in the user edit panel of the network admin -> Users;


Version 1.0.0 - Initial Release