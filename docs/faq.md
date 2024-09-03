# FAQ

## How do I … ?

### Send historical data

Use the console. As SDG-plugin uses archived data, you need to  invalidate reports historically for the SDG plugin, like (for site id 1):

```sh
./console core:invalidate-report-data --dates=2024-08-01,2024-08-31 --sites=1 --plugin=SDG
```

After that you can wait until Matomo has archived your data, or you could archive manually:

```sh
./console core:archive --force-date-range=2024-08-01,2024-08-31 --force-idsites=1
```

The next step is to create the report to be sent (this is normally done automatically), the recommendation is to send in one report per month:

```sh
./console sdg:send-statistics-on-information-services --idsite=1 --from=2024-08-01 --to=2024-08-31
```

This will output in the terminal what would be sent, to send it for real, add the send flag.

```sh
./console sdg:send-statistics-on-information-services --idsite=1 --from=2024-08-01 --to=2024-08-31 --send
```

You should get a successful output when this is done.

### List sent reports?

In the Administration section, go to Single Digital Gateway -> Send status.

Successfully sent reports should have a response “200”, if not, you can try to resend them by pushing the resend icon. Though, we recommend to use the console to send the reports, for better debugging output if something is wrong. A new uuid for identifying the reports will  be created.

Example:

```sh
./console sdg:send-statistics-on-information-services --idsite=1 --from=2024-08-01 --to=2024-08-31 --send
```
