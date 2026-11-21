# Matomo Single Digital Gateway (SDG) Plugin

Plugin for Single Digital Gateway for Your Europe.

## Use for plugin

You only need this plugin for Matomo if your website analytics needs
to be report to the single digital gateway, and you need the API keys
to do so.

## Requirements

- An API key for the Single Digital Gateway.
- Matomo's scheduled tasks have to run, as they do with the `core:archive` cron job. They send the statistics.
- The Matomo server has to reach `https://collect.youreurope.europa.eu`, and `https://collect.sdgacceptance.eu` for testing. A proxy set in Matomo's `config.ini.php` is used.

## Installation

### Download and activate plugin

Just follow your normal procedure for downloading plugins, and then activate the
plugin. And follow the instructions bellow.

### Per website

Go to Admin --> Websites --> Manage. edit the site that you are getting to send
SDG data for. In `SDG API key`, add your API key to SDG. if you want to test,
you should also add the key for `Testing SDG API key`. If testing, see further
down for using Acceptance environment.

In `SDG Report URLs`, add all Urls that should be reported to SDG, comma separated.
A page is reported when its address contains one of them. Write them without `https://` and `www.`, for example `example.org/services`.

From the command line:

```sh
SDG_API_KEY=<key> ./console sdg:set-api-key --idsite=<idSite>
./console sdg:set-urls --idsite=<idSite> --urls=example.org/services,example.org/permits
```

### Production or Acceptance

The default is that you are sending data to the Production endpoint, for testing, you
could use Acceptance instead.

Go to Admin --> General settings --> SDG and choose "Acceptance" and save.

All API requests will now go against Acceptance API instead of the live, production
API.

## Tests

```sh
./console tests:run SDG
```

The SDG portal is replaced by answers of the tests. One test sends a transfer to the acceptance environment of the portal instead. It is skipped unless these are set:

- `SDG_ACCEPTANCE_API_KEY`: a key for the acceptance environment
- `SDG_ACCEPTANCE_URL`: the address to report, without `https://`

## Support

For professional support and consultation, contact Digitalist Open Cloud <cloud@digitalist.com> or [visit or website](https://digitalist.cloud/services/eu/sdg-plugin-for-matomo).

## License

Copyright (C) 2024 Digitalist Open Cloud <cloud@digitalist.com>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <https://www.gnu.org/licenses/>.
