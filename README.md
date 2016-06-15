# menu-builder-client-bundle

A client for the Menu Builder Bundle

- Feature: Make it possible to completely ignore route parameters such as `_locale`, `_scheme` (maybe `_host`?) and use those from the current environment - a valid use case for that is if a user is browsing the french version of the website, but I want all links on my menu to link to pages in french and not the default fallback to english for instance.
- BUG: default value for _locale, then select some value - still generates with default value
- UX: Better handling of route requirements such as asd(ffs|xd)|dasdf - look at fgetcsv
