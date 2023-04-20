<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Traffic 0.8.30

Zugriffsanalysen aus Logdateien erstellen.

<p align="center"><img src="traffic-screenshot.png?raw=true" alt="Bildschirmfoto"></p>

## Wie man eine Erweiterung installiert

[ZIP-Datei herunterladen](https://github.com/annaesvensson/yellow-traffic/archive/main.zip) und in dein `system/extensions`-Verzeichnis kopieren. [Weitere Informationen zu Erweiterungen](https://github.com/annaesvensson/yellow-update/tree/main/README-de.md).

## Wie man Zugriffsanalysen erstellt

Die Zugriffsanalysen sind in der [Befehlszeile](https://github.com/annaesvensson/yellow-core/tree/main/README-de.md) vorhanden. Es zeigt Seitenaufrufe, beliebte Inhalte, Dateien zum Herunterladen und Suchanfragen. Öffne ein Terminalfenster. Gehe ins Installations-Verzeichnis, dort wo sich die Datei `yellow.php` befindet. Gib ein `php yellow.php traffic` gefolgt von optionalen Tagen, Datum und Ort.

## Beispiele

Zugriffsanalysen in der Befehlszeile erstellen:

`php yellow.php traffic`  

Zugriffsanalysen in der Befehlszeile erstellen, unterschiedliche Anzahl Tage:

`php yellow.php traffic 1`  
`php yellow.php traffic 7`  
`php yellow.php traffic 30`  

Zugriffsanalysen in der Befehlszeile erstellen, unterschiedliches Datum:

`php yellow.php traffic 30 2021-06-01`  
`php yellow.php traffic 30 2021-09-01`  
`php yellow.php traffic 30 2021-12-01`  

Zugriffsanalysen in der Befehlszeile erstellen, unterschiedliche Orte:

`php yellow.php traffic 30 2021-06-01 /wiki/`  
`php yellow.php traffic 30 2021-06-01 /blog/`  
`php yellow.php traffic 30 2021-06-01 /search/`  

Zugriffsanalysen in der Befehlszeile erstellen, unterschiedliche Orte für das aktuelle Datum:

`php yellow.php traffic 30 - /wiki/`  
`php yellow.php traffic 30 - /blog/`  
`php yellow.php traffic 30 - /search/`  

Verschiedene Zugriffsanalysen in den Einstellungen festlegen:

```
TrafficAnalytics: view, content, download, search
TrafficAnalytics: view, content, download, search, referring
TrafficAnalytics: view, request, download, missing, error
```

Verschiedene Spamfilter in den Einstellungen festlegen:

```
TrafficSpamFilter: bot|crawler|spider|checker
TrafficSpamFilter: bot|crawler|spider|checker|youtube.com|instagram.com|twitter.com
TrafficSpamFilter: bot|crawler|spider|checker|www.google|duckduckgo.com|bing.com|baidu.com
```

## Einstellungen

Die folgenden Einstellungen können in der Datei `system/extensions/yellow-system.ini` vorgenommen werden:

`TrafficStaticUrl` = URL der Webseite bei Verwendung der Befehlszeile  
`TrafficLogDirectory` = Verzeichnis mit Webserver-Logdateien  
`TrafficAccessFile` = Dateiname als regulärer Ausdruck  
`TrafficAnalytics` = Zugriffsanalysen die angezeigt werden, [unterstützte Analysen](#einstellungen-analytics)  
`TrafficLinesMax` = Anzahl der Zeilen pro Analyse  
`TrafficDays` = Anzahl der Tage  
`TrafficSpamFilter` = Spamfilter als regulärer Ausdruck, `none` um zu deaktivieren  

<a id="einstellungen-analytics"></a>Die folgenden Zugriffsanalysen werden unterstützt:

`view` = Diagramm mit Seitenaufrufen pro Tag/Stunde  
`request` = Diagramm mit HTTP-Anfragen pro Tag/Stunde  
`content` = Liste beliebter Inhalte  
`download` = Liste beliebter Dateien zum Herunterladen  
`search` = Liste der Suchanfragen  
`referring` = Liste der verweisenden Webseiten  
`missing` = Liste der fehlenden Dateien  
`error` = Liste der Fehlerseiten  

## Entwickler

Anna Svensson. [Hilfe finden](https://datenstrom.se/de/yellow/help/).
