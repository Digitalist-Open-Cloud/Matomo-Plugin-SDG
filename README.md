# Matomo Single Digital Gateway (SDG) Plugin

Plugin for Single Digital Gateway for Your Europe.

## Installation

### Per website

Go to Admin --> Websites --> Manage. edit the site that you are getting to send
SDG data for. In `SDG API key`, add your API key to SDG. if you want to test,
you should also add the key for `Testing SDG API key`. If testing, see further
down for using Acceptance environment.

In `SDG Report URLs`, add all Urls that should be reported to SDG, comma separated.

### Production or Acceptance

The default is that you are sending data to the Production endpoint, for testing, you
could use Acceptance instead.

Go to Admin --> General settings --> SDG and choose "Acceptance" and save.

All API requests will now go against Acceptance API instead of the live, production
API.

## Support

For Professional support and consultation, contact Digitalist Open Cloud <cloud@digitalist.com> or [visit or website](https://digitalist.cloud/services/eu/sdg-plugin-for-matomo).

## License

Copyright (C) 2024 Digitalist Open Cloud <cloud@digitalist.com>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.  If not, see <https://www.gnu.org/licenses/>.
