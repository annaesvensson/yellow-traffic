<p align="right"><a href="README-de.md">Deutsch</a> &nbsp; <a href="README.md">English</a> &nbsp; <a href="README-sv.md">Svenska</a></p>

# Traffic 0.8.32

Skapa trafikanalyser från loggfiler.

<p align="center"><img src="traffic-screenshot.png?raw=true" alt="Skärmdump"></p>

## Hur man installerar ett tillägg

[Ladda ner ZIP-filen](https://github.com/annaesvensson/yellow-traffic/archive/refs/heads/main.zip) och kopiera den till din `system/extensions` mapp. [Läs mer om tillägg](https://github.com/annaesvensson/yellow-update/tree/main/README-sv.md).

## Hur man skapar trafikanalyser

Du kan skapa trafikanalyser på [kommandoraden](https://github.com/annaesvensson/yellow-core/tree/main/README-sv.md). Detta låter dig utvärdera sidvisningar, populärt innehåll, populära nedladdningar och sökfrågor. Öppna ett terminalfönster. Gå till installationsmappen där filen `yellow.php` finns. Skriv `php yellow.php traffic`, du kan lägga till valfria dagar och plats. Detta kommer att skapa trafikanalyser och visa dem på skärmen.

## Exempel

Skapa trafikanalyser på kommandoraden:

`php yellow.php traffic`  

Skapa trafikanalyser på kommandoraden, olika antal dagar:

`php yellow.php traffic 1`  
`php yellow.php traffic 7`  
`php yellow.php traffic 30`  

Skapa trafikanalyser på kommandoraden, olika datum:

`php yellow.php traffic 30 2021-06-01`  
`php yellow.php traffic 30 2021-09-01`  
`php yellow.php traffic 30 2021-12-01`  

Skapa trafikanalyser på kommandoraden, olika platser:

`php yellow.php traffic 30 2021-06-01 /wiki/`  
`php yellow.php traffic 30 2021-06-01 /blog/`  
`php yellow.php traffic 30 2021-06-01 /search/`  

Skapa trafikanalyser på kommandoraden, olika platser för aktuella datumet:

`php yellow.php traffic 30 - /wiki/`  
`php yellow.php traffic 30 - /blog/`  
`php yellow.php traffic 30 - /search/`  

Konfigurera olika trafikanalyser i inställningar:

```
TrafficAnalytics: view, content, download, search
TrafficAnalytics: view, content, download, search, referring
TrafficAnalytics: view, request, download, missing, error
```

Konfigurera olika skräplänkfilter i inställningar:

```
TrafficSpamFilter: bot|crawler|spider|checker
TrafficSpamFilter: bot|crawler|spider|checker|facebook.com|youtube.com|instagram.com
TrafficSpamFilter: bot|crawler|spider|checker|www.google|duckduckgo.com|bing.com|baidu.com
```

## Inställningar

Följande inställningar kan konfigureras i filen `system/extensions/yellow-system.ini`:

`TrafficStaticUrl` = webbplatsens URL när man använder kommandoraden  
`TrafficLogDirectory` = mapp med webbserverns logfiler  
`TrafficAccessFile` = filnamn som reguljära uttryck  
`TrafficAnalytics` = trafikanalyser som visas, [stödda analyser](#inställningar-analytics)  
`TrafficLinesMax` = antal rader per analys  
`TrafficDays` = antal dagar  
`TrafficSpamFilter` = skräplänkfilter som reguljära uttryck, `none` för att inaktivera  

<a id="inställningar-analytics"></a>Följande trafikanalyser stöds:

`view` = graf med sidvisningar per dag/timme  
`request` = graf med HTTP-förfrågningar per dag/timme  
`content` = lista över populärt innehåll  
`download` = lista över populära nedladdningar  
`search` = lista över sökfrågor  
`referring` = lista över refererande webbplatser  
`missing` = lista över saknade sidor  
`error` = lista över felsidor  

## Utvecklare

Anna Svensson. [Få hjälp](https://datenstrom.se/sv/yellow/help/).
