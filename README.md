# IMAP functionality for the Booosta Framework

This modules provides IMAP functions to retrieve and manage messages from IMAP accounts.

Booosta allows to develop PHP web applications quick. It is mainly designed for small web applications.
It does not provide a strict MVC distinction. Although the MVC concepts influence the framework. Templates,
data objects can be seen as the Vs and Ms of MVC.

Up to version 3 Booosta was available at Sourceforge: https://sourceforge.net/projects/booosta/ From version
4 on it resides on Github and is available from Packagist under booosta/booosta .

## Installation

This module can be used inside the Booosta framework. If you want to do so, install the framework first. See the
[installation instructions](https://github.com/buzanits/booosta-installer) for accomplishing this. If your
Booosta is installed, you can install this module.

You also can use this module in your standalone PHP scripts. In both cases you install it with:

```
composer require booosta/imap
```

## Usage in the Booosta framework

In your scripts you use the module:

```
$imap = $this->makeInstance('imap', $server, $username, $password, $options);

// Get the number of messages in Mailbox
$count = $imap->get_msg_num();

// Get the list of folders
$folders = $imap->get_folderlist();

// Get a specific message. The format of the message is explained later.
$msg = $imap->get_mail_message($number);

// Get the last message in the mailbox
$msg = $imap->get_last_message($delete = false);

// Move a specific mail to a different location
$imap->move_mail($number, $destination);

// Delete a specific mail
// $expunge means remove the mail from server immediately, not on closing of mailbox
$imap->delete_message($number, $expunge = true);

// Create a new folder
$imap->create_folder($name);

// Get the size of the complete mailbox
// returns Megabyte or Byte if $bytes is set to true
$size = $imap->get_size($bytes = false);
```

## Options

The last parameter of the constructor is an optional array that holds options

```
$options['tls'] = true;               // use TLS
$options['novalidate-cert'] = true;   // do not validate servers certificate
$options['raise_error'] = true;       // raise a booosta error when a problem arises
```

## Usage as Standalone Module

Just replace the first line of the above example with:

```
$imap = new \booosta\imap\Imap($server, $username, $password, $options);
```

## Message Object

`get_mail_message()` and `get_last_message()` return an object with the following methods

```
$msg = $imap->get_mail_message($number);

print PHP_EOL . 'Sender: ' . $msg->get_sender();
print PHP_EOL . 'Recepient: ' . $msg->get_recepient();
print PHP_EOL . 'Recepient: ' . $msg->get_recipient();  // for those always typing it wrong ;-)
print PHP_EOL . 'Subject: ' . $msg->get_subject();
print PHP_EOL . 'Text: ' . $msg->get_text();
print PHP_EOL . 'Raw Text: ' . $msg->get_rawtext();
print PHP_EOL . 'Date/Time: ' . $msg->get_dtime();
```
