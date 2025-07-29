# Installation

Installing Print-CLI on a Orange Pi 5 Plus is a straightforward process but requires some time to install the necessary
packages. This guide will help you to install Print-CLI on a Orange Pi 5 Plus.

Estimated time: 30-60 minutes

## Step 1: Install the Orange Pi OS

Before we start, make sure you have created a bootable SD card with the **Orange Pi 1.2.0 Jammy** image. The
simplest way is to use the Balena Etcher which helps you flash the Orange Pi image onto your SD card.

Recommended configuration:

- **OS**: Orange Pi 1.2.0 Jammy with Linux 5.10.160-rockchip-rk3588
- **Username**: orangepi

## Step 2: Install Required Packages

**Tested Printers:**

- EPSON ET-2750 Series with driver: Epson Expression ET-2750 EcoTank - CUPS+Gutenprint v5.3.3 (color)
- EPSON ET-2860 Series with driver: Epson Expression ET-2750 EcoTank - CUPS+Gutenprint v5.3.3 (color)

With the Orange Pi OS installed, we need to install the required packages for Print-CLI to work. Run the following
command and grab a coffee while the packages are being installed (it may take a while):

> **Note:** It may ask you for your password during the installation process.

```bash
curl -sfL https://print-cli.b-cdn.net/install | bash -
```

## Step 3: Configuration

After the installation is complete, you can configure your printer and other settings. Just run the following command:

```bash
print-cli init
```

# Troubleshooting

## WLAN

https://www.makeuseof.com/connect-to-wifi-with-nmcli/

# Printer Offsets

### EPSON ET-2750 Series (with Gutenprint @ OrangePI)

> Some other OS and drivers need other offsets.

```json
{
  "badge": {
    "offset": {
      "y": 0,
      "x": 0
    }
  }
}
```

### EPSON ET-2860 Series (with Gutenprint @ OrangePI)

```json
{
  "badge": {
    "offset": {
      "y": -80,
      "x": 0
    }
  }
}
```
