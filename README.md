<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Traffic 0.8.32

Create traffic analytics from log files.

<p align="center"><img src="traffic-screenshot.png?raw=true" alt="Screenshot"></p>

## How to install an extension

[Download ZIP file](https://github.com/annaesvensson/yellow-traffic/archive/main.zip) and copy it into your `system/extensions` folder. [Learn more about extensions](https://github.com/annaesvensson/yellow-update).

## How to create traffic analytics

You can create traffic analytics at the [command line](https://github.com/annaesvensson/yellow-core). This allows you to evaluate page views, popular content, popular downloads and search queries. Open a terminal window. Go to your installation folder, where the file `yellow.php` is. Type `php yellow.php traffic`, you can add optional days, date and location. This will create traffic analytics and show them on screen.

## Examples

Creating traffic analytics at the command line:

`php yellow.php traffic`  

Creating traffic analytics at the command line, different number of days:

`php yellow.php traffic 1`  
`php yellow.php traffic 7`  
`php yellow.php traffic 30`  

Creating traffic analytics at the command line, different dates:

`php yellow.php traffic 30 2021-06-01`  
`php yellow.php traffic 30 2021-09-01`  
`php yellow.php traffic 30 2021-12-01`  

Creating traffic analytics at the command line, different locations:

`php yellow.php traffic 30 2021-06-01 /wiki/`  
`php yellow.php traffic 30 2021-06-01 /blog/`  
`php yellow.php traffic 30 2021-06-01 /search/`  

Creating traffic analytics at the command line, different locations for the current date:

`php yellow.php traffic 30 - /wiki/`  
`php yellow.php traffic 30 - /blog/`  
`php yellow.php traffic 30 - /search/`  

Configuring different traffic analytics in the settings:

```
TrafficAnalytics: view, content, download, search
TrafficAnalytics: view, content, download, search, referring
TrafficAnalytics: view, request, download, missing, error
```

Configuring different spam filters in the settings:

```
TrafficSpamFilter: bot|crawler|spider|checker
TrafficSpamFilter: bot|crawler|spider|checker|facebook.com|youtube.com|instagram.com
TrafficSpamFilter: bot|crawler|spider|checker|www.google|duckduckgo.com|bing.com|baidu.com
```

## Settings

The following settings can be configured in file `system/extensions/yellow-system.ini`:

`TrafficStaticUrl` = URL of the website when using the command line  
`TrafficLogDirectory` = directory with web server log files  
`TrafficAccessFile` = file name as regular expression  
`TrafficAnalytics` = traffic analytics shown, [supported analytics](#settings-analytics)  
`TrafficLinesMax` = number of lines per analytics  
`TrafficDays` = number of days  
`TrafficSpamFilter` = spam filter as regular expression, `none` to disable  

<a id="settings-analytics"></a>The following traffic analytics are supported:

`view` = graph with page views per day/hour  
`request` = graph with HTTP requests per day/hour  
`content` = list of popular content  
`download` = list of popular downloads  
`search` = list of search queries  
`referring` = list of referring sites  
`missing` = list of missing pages  
`error` = list of error pages  

## Developer

Anna Svensson. [Get help](https://datenstrom.se/yellow/help/).
