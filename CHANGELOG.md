# Change log

## [Unreleased]

### Security

- Values are bound in all database queries of the plugin.
- Report URLs are matched as text when archiving. They are no longer part of the query, and a URL with special characters no longer stops archiving.
- Sending a transfer again needs admin access to its website and the token of the status page. What is sent is read from the recorded transfer.
- The status page only lists transfers of websites the user is admin of.
- The report checks view access itself.
- The documentation and support pages need admin access.
- The answer of the SDG portal is no longer printed.
- Requests to the SDG portal use Matomo's HTTP client, with a timeout and the proxy settings of Matomo.
- `sdg:set-api-key` can read the key from the environment variable `SDG_API_KEY`, to keep it out of the history of the shell.

### Fixed

- The status page can be filtered by website.
- A month is sent from the send date on until the portal has accepted it, once a day. Before, it was only tried on the send date itself, so a month was lost when that day was missed or the send date was a day the month does not have.
- `sdg:send-statistics-on-information-services` refuses a period that does not start on the first day of a month, as the portal does.
- The status page shows the time of a transfer and not only its day.
- A website was left out of the scheduled sending when its report URLs started with a comma, which `sdg:set-urls` wrote for the first URLs of a website.

### Changed

- Only the report URLs of a website are used for that website. Before, the URLs of all websites were.
- A website without report URLs archives nothing for the plugin.
- A transfer that is sent again replaces what was recorded for it, and the status the portal answered with is recorded.
- Sending stops when the portal does not give out a unique ID.

## [5.1.1] - 2024-09-05

### Added

- Improve documentation

## [5.1.0] - 2024-09-05

### Added

- Plugins first release
