# Installation

Installing Print-CLI on a Raspberry Pi is a quite simple process but requires some time installing the required
packages. This guide will help you to install Print-CLI on a Raspberry Pi.

Estimated time: 30-60 minutes

## Step 1: Install the Raspberry Pi OS

Before we start, make sure you have created a bootable SD card with the **Ubuntu Server 22.04 LTS 64-bit** image. The
simplest way is to use the Raspberry Pi Imager which enables you to select an Ubuntu image when flashing your SD card.

Recommended configuration:

- **OS**: Ubuntu Server 22.04 LTS 64-bit
- **Username**: print-cli

## Step 2: Install Required Packages

Next, we need to install the required packages for Print-CLI to work. Run the following commands and grab a coffee while
the packages are being installed (it may take a while):

```bash
sudo apt-get update
sudo add-apt-repository ppa:ondrej/php
sudo apt-get install -y git cups zip unzip supervisor \
  composer php-zip php-curl php-xml php-mbstring
```

Finally, add the Composer bin directory to your PATH, so you can run the `print-cli` command from anywhere:

```bash
echo 'export PATH="$PATH:$HOME/.config/composer/vendor/bin"' >> ~/.bashrc
```

## Step 3: Ensure CUPS is Running

Now we need to ensure that the CUPS service is running. CUPS is the printing system used by Print-CLI to send print jobs
to the printer. This is the most important step, so make sure you follow it carefully, otherwise, Print-CLI won't work
as expected, and you won't be able to print anything.

```bash
sudo cupsctl --remote-admin --remote-any
sudo usermod -aG lpadmin print-cli
sudo /etc/init.d/cups restart
```

Make sure the CUPS service is running by visiting the following URL in your browser:

```text
https://10.20.0.195:631/printers/
```

You should see a page with a list of printers. If you don't see any printers, you may need to add one manually.

Ensure that you can print a test page by clicking on the printer name and selecting "Print Test Page".

### Find the Printer Address

To find the printer address, visit your Printers page in CUPS, and click on the printer name. The address in the browser
should look like this:

```text
https://10.20.0.195:631/printers/EPSON_ET_2720_Series
```

It contains the Name of the printer, which is the `printer-name` part. You can use this address in the configuration
file for Print-CLI. In most cases, just replace the `https` with `ipp` and remove the `/printers` part:

```text
ipp://127.0.0.1:631/printers/EPSON_ET_2720_Series
```

## Step 4: Install Print-CLI

So far, we have installed all the required packages and ensured that CUPS is running. Now we can install Print-CLI using
Composer, the PHP package manager. Run the following command to install Print-CLI globally:

```bash
composer global require anikeen/print-cli
```

You can also use the same command to update Print-CLI to the latest version:

```bash
composer global require anikeen/print-cli
```

## Step 5: Configure Print-CLI

In this tutorial, we're using the home directory of the `print-cli` user to store the configuration file. If you're
using a different user, make sure to replace `print-cli` with the correct username.

Next create a configuration file `~/print-cli.yml`, and add the following content:

> Make sure to replace the `license_key`, `your-printer-uuid`, `address`, `username`, and `password` with your own
> values. Also make sure to replace the `base_url` with the correct URL to our events platform.

```yaml
base_url: 'https://events.anikeen.com'
license_key: 'your-license-key'
printers:
  - id: 'your-printer-uuid'
    name: EPSON ET 2750
    driver: cups
    address: 'ipp://127.0.0.1:631/printers/EPSON_ET_2720_Series'
    username: 'print-cli'
    password: 'password'
```

To test the configuration, run the following command:

```bash
print-cli serve
```

If everything is configured correctly, you should see the following output:

```text
Starting service...
Reading configuration...
Service started!
```

You can exit the service by pressing `Ctrl+C`.

## Step 6: Supervisor Configuration

To run Print-CLI as a service, we can use Supervisor. Supervisor is a process control system that allows you to monitor
and control a number of processes on UNIX-like operating systems.

Create a new configuration file `/etc/supervisor/conf.d/print-cli.conf`:

```ini
[program:print-cli]
directory = /home/print-cli
command = /usr/bin/php /home/print-cli/.config/composer/vendor/bin/print-cli serve
autostart = true
autorestart = true
stderr_logfile = /var/log/print-cli.err.log
stdout_logfile = /var/log/print-cli.out.log
stopwaitsecs = 3600
user = print-cli
```

Now, update Supervisor to read the new configuration file and start the Print-CLI service:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start print-cli
```
