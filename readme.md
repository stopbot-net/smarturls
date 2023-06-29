# SmartURLs

[![License](https://img.shields.io/badge/license-CC0%201.0%20Universal-blue.svg)](https://creativecommons.org/publicdomain/zero/1.0/)
[![GitHub Issues](https://img.shields.io/github/issues/stopbot-net/smarturls.svg)](https://github.com/stopbot-net/smarturls/issues)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/stopbot-net/smarturls/pulls)

A smart URL refers to a URL that has been enhanced with various features that provide additional advantages. These features may include visitor tracking, customized display based on geographical location, redirection to different links based on the device used, advanced analytics, or the use of specific parameters to send additional data to the destination page. Smart URLs allow users to optimize and manage user experiences better and gain deeper insights into their users.

## Features

- Create smart URLs with customizable rules and actions.
- Redirect users to different destinations based on the defined rules.
- Support for URL rewriting using regular expressions.
- Easy configuration of rules and actions through a configuration file.
- Controlled by the stopbot panel.

## Installation

1. Clone this repository to your local machine:
```
git clone https://github.com/stopbot-net/smarturls.git
```
2. Navigate to the project directory:
```
cd smarturls
```
3.Edit config.php then change with your APIKey, [Click Here](https://stopbot.net/apikey) for APIKey
```
<?php
/*
     _              _           _                _   
    | |            | |         | |              | |  
 ___| |_ ___  _ __ | |__   ___ | |_   _ __   ___| |_ 
/ __| __/ _ \| '_ \| '_ \ / _ \| __| | '_ \ / _ \ __|
\__ \ || (_) | |_) | |_) | (_) | |_ _| | | |  __/ |_ 
|___/\__\___/| .__/|_.__/ \___/ \__(_)_| |_|\___|\__|
             | |                                     
             |_|                                     
                      [Stopbot SmartUrls v.1.2]

Guide   : https://docs.stopbot.net/service-guides/stopbot/smart-urls
Website : stopbot.net
contact : t.me @stopbotnet

*/


#Put your Apikey here.
$Apikey = "________________________________";
```
4. You can create Shortlinks / SmartURLs in our control panel, [Click Here](https://docs.stopbot.net/panel-guides/services/stopbot/smart-urls) for details.
5. To access the results of the Shortlinks / SmartURLs, please open https://domain/keyname or you can view them in our control panel, [Click Here](https://docs.stopbot.net/panel-guides/services/stopbot/smart-urls) for details.
6. For information regarding visitor statistics, [Click Here](https://docs.stopbot.net/panel-guides/services/stopbot/smart-urls) for details.

## API Documentation

[https://docs.stopbot.net/api-documentation/smart-urls](https://docs.stopbot.net/api-documentation/smart-urls)

## Full Integration Guide
[https://docs.stopbot.net/service-guides/stopbot/smart-urls](https://docs.stopbot.net/service-guides/stopbot/smart-urls)
