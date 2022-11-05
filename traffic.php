<?php
// Traffic extension, https://github.com/annaesvensson/yellow-traffic

class YellowTraffic {
    const VERSION = "0.8.29";
    public $yellow;         // access to API
    public $days;           // number of days
    public $views;          // number of views
    public $requests;       // number of requests

    // Handle initialisation
    public function onLoad($yellow) {
        $this->yellow = $yellow;
        $this->yellow->system->setDefault("trafficStaticUrl", "auto");
        $this->yellow->system->setDefault("trafficLogDirectory", "/var/log/apache2/");
        $this->yellow->system->setDefault("trafficAccessFile", "(.*)access.log");
        $this->yellow->system->setDefault("trafficAnalytics", "view, content, download, search");
        $this->yellow->system->setDefault("trafficLinesMax", 8);
        $this->yellow->system->setDefault("trafficDays", 30);
        $this->yellow->system->setDefault("trafficSpamFilter", "bot|crawler|spider|checker|localhost");
    }

    // Handle command
    public function onCommand($command, $text) {
        switch ($command) {
            case "traffic": $statusCode = $this->processCommandTraffic($command, $text); break;
            default:        $statusCode = 0;
        }
        return $statusCode;
    }

    // Handle command help
    public function onCommandHelp() {
        return "traffic [days date location]";
    }
    
    // Process command to create traffic analytics
    public function processCommandTraffic($command, $text) {
        $statusCode = 0;
        list($days, $date, $location) = $this->yellow->toolbox->getTextArguments($text);
        if (is_string_empty($location) || substru($location, 0, 1)=="/") {
            if ($this->checkStaticSettings()) {
                $statusCode = $this->processRequests($days, $date, $location);
            } else {
                $statusCode = 500;
                $this->days = $this->views = $this->requests = 0;
                $fileName = $this->yellow->system->get("coreExtensionDirectory").$this->yellow->system->get("coreSystemFile");
                echo "ERROR checking files: Please configure TrafficStaticUrl in file '$fileName'!\n";
            }
            echo "Yellow $command: $this->days day".($this->days!=1 ? "s" : "").", ";
            echo "$this->views view".($this->views!=1 ? "s" : "").", ";
            echo "$this->requests request".($this->views!=1 ? "s" : "")."\n";
        } else {
            $statusCode = 400;
            echo "Yellow $command: Invalid arguments\n";
        }
        return $statusCode;
    }
    
    // Analyse and show traffic
    public function processRequests($days, $date, $location) {
        if (is_string_empty($days)) $days = $this->yellow->system->get("trafficDays");
        if (is_string_empty($location)) $location = "/";
        $path = $this->yellow->system->get("trafficLogDirectory");
        $regex = "/^".basename($this->yellow->system->get("trafficAccessFile"))."$/";
        $fileNames = array_reverse($this->yellow->toolbox->getDirectoryEntries($path, $regex, true, false));
        list($statusCode, $view, $request, $content, $download, $search, $referring, $missing, $error) =
            $this->analyseRequests($days, $date, $location, $fileNames);
        if ($statusCode==200) {
            $graphUnit = $days<=7 ? "per hour" : "per day";
            foreach (preg_split("/\s*,\s*/", $this->yellow->system->get("trafficAnalytics")) as $analytics) {
                switch ($analytics) {
                    case "view":      $this->showRequestsGraph($view, "Page views $graphUnit"); break;
                    case "request":   $this->showRequestsGraph($request, "HTTP requests $graphUnit"); break;
                    case "content":   $this->showRequestsList($content, "Popular content"); break;
                    case "download":  $this->showRequestsList($download, "Popular downloads"); break;
                    case "search":    $this->showRequestsList($search, "Search queries"); break;
                    case "referring": $this->showRequestsList($referring, "Referring sites"); break;
                    case "missing":   $this->showRequestsList($missing, "Missing pages"); break;
                    case "error":     $this->showRequestsList($error, "Error pages"); break;
                }
            }
        }
        return $statusCode;
    }
    
    // Analyse traffic from web server log files
    public function analyseRequests($days, $date, $locationFilter, $fileNames) {
        $this->days = $this->views = $this->requests = 0;
        $view = $request = $content = $download = $search = $referring = $missing = $error = array();
        if (!is_array_empty($fileNames)) {
            $statusCode = 200;
            if (is_string_empty($date)) {
                $timeStart = $timeFound = (intval(time()/3600)*3600)+3600;
                $timeStop = $timeStart - (60*60*24*$days);
            } else {
                $timeStop = (intval(strtotime($date)/3600)*3600)+3600;
                $timeStart = $timeFound = $timeStop + (60*60*24*$days);
            }
            $percentShown = -1;
            $indexMax = $days<=7 ? 24*$days : $days;
            $indexTimespan = $days<=7 ? 3600 : 86400;
            for ($index=0; $index<$indexMax; ++$index) $view[$index] = $request[$index] = 0;
            $staticUrl = $this->yellow->system->get("trafficStaticUrl");
            list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($staticUrl);
            $locationSearch = $this->yellow->system->get("searchLocation");
            $spamFilter = $this->yellow->system->get("trafficSpamFilter");
            $locationDownload = $this->yellow->system->get("coreDownloadLocation");
            $locationIgnore = "(".$this->yellow->system->get("coreMediaLocation")."|".$this->yellow->system->get("editLocation").")";
            foreach ($fileNames as $fileName) {
                if ($this->yellow->system->get("coreDebugMode")>=2) echo "YellowTraffic::analyseRequests file:$fileName\n";
                $fileHandle = @fopen($fileName, "r");
                if ($fileHandle) {
                    list($timestampFirst) = $this->getLineArguments($this->getFileLineFirst($fileHandle));
                    $timeFirst = strtotime($timestampFirst);
                    list($timestampLast) = $this->getLineArguments($this->getFileLineLast($fileHandle, filesize($fileName)));
                    $timeLast = strtotime($timestampLast);
                    if (($timeFirst>=$timeStop && $timeFirst<=$timeStart) ||
                        ($timeLast>=$timeStop && $timeLast<=$timeStart) ||
                        ($timeFirst<=$timeStop && $timeLast>=$timeStart)) {
                        if ($this->yellow->system->get("coreDebugMode")>=2) {
                            $debug = date("Y-m-d H:i:s", $timeFirst)." - ".date("Y-m-d H:i:s", $timeLast);
                            echo "YellowTraffic::analyseRequests $debug<br/>\n";
                        }
                    } else {
                        continue;
                    }
                    $filePos = filesize($fileName)-1;
                    $fileTop = -1;
                    while (($line = $this->getFileLinePrevious($fileHandle, $filePos, $fileTop, $dataBuffer))!==false) {
                        list($timestamp, $method, $uri, $protocol, $status, $referer, $userAgent) = $this->getLineArguments($line);
                        $timeFound = strtotime($timestamp);
                        if ($timeFound>$timeStart) continue;
                        if ($timeFound<$timeStop) break;
                        $percent = $this->getProgressPercent(($timeStart-$timeFound)/3600, 24*$days, 5, 95);
                        if ($percentShown!=$percent) {
                            $percentShown = $percent;
                            echo "\rCreating traffic analytics $percent%... ";
                        }
                        $location = $this->getLocation($uri);
                        if (!preg_match("#^$base$locationFilter#", $location)) continue;
                        $referer = $this->getReferer($referer, "$address$base/");
                        $url = $this->getUrl($scheme, $address, $base, $location);
                        $urlSearch = $this->getUrlSearch($scheme, $address, $base, $location, $locationSearch);
                        $urlSite = $this->getUrlSite($referer);
                        $index = $indexMax-intval(($timeStart-$timeFound)/$indexTimespan)-1;
                        ++$this->requests;
                        ++$request[$index];
                        if ($status<400) {
                            if ($status==206 || ($status>=301 && $status<=303)) continue;
                            if (!$this->checkRequestArguments($method, $location, $referer)) continue;
                            if ($spamFilter!="none" && preg_match("#$spamFilter#i", $referer.$userAgent)) continue;
                            if (preg_match("#^$base$locationDownload#", $location)) {
                                if (!isset($download[$url])) $download[$url] = 0;
                                ++$download[$url];
                            }
                            if (preg_match("#^$base$locationIgnore#", $location) && $locationFilter=="/") continue;
                            if (preg_match("#^$base/robots\.txt#", $location) && $locationFilter=="/") continue;
                            ++$this->views;
                            ++$view[$index];
                            if (!isset($content[$url])) $content[$url] = 0;
                            ++$content[$url];
                            if (!isset($referring[$urlSite])) $referring[$urlSite] = 0;
                            ++$referring[$urlSite];
                            if (!isset($search[$urlSearch])) $search[$urlSearch] = 0;
                            ++$search[$urlSearch];
                        } elseif ($status==404 || $status==434 || $status==435) {
                            $entry = $this->getUrl($scheme, $address, $base, $location)." - ".$this->getStatusFormatted($status);
                            if (!isset($missing[$entry])) $missing[$entry] = 0;
                            ++$missing[$entry];
                        } else {
                            $entry = $this->getUrl($scheme, $address, $base, $location)." - ".$this->getStatusFormatted($status);
                            if (!isset($error[$entry])) $error[$entry] = 0;
                            ++$error[$entry];
                        }
                    }
                    fclose($fileHandle);
                } else {
                    $statusCode = 500;
                    echo "ERROR reading log files: Can't read file '$fileName'!\n";
                }
            }
            unset($referring["-"]);
            unset($search["-"]);
            if ($locationFilter!="/") $search = array();
            $this->days = $timeStart!=$timeFound ? $days : 0;
            echo "\rCreating traffic analytics 100%... done\n";
        } else {
            $statusCode = 500;
            $path = $this->yellow->system->get("trafficLogDirectory");
            echo "ERROR reading log files: Can't find files in directory '$path'!\n";
        }
        return array($statusCode, $view, $request, $content, $download, $search, $referring, $missing, $error);
    }
    
    // Show requests as list sorted by frequency
    public function showRequestsList($data, $text) {
        if (!is_array_empty($data)) {
            uasort($data, "strnatcasecmp");
            $data = array_reverse($data);
            $data = array_slice($data, 0, $this->yellow->system->get("trafficLinesMax"));
            $valueMax = max($data);
            echo "$text\n\n";
            foreach ($data as $key=>$value) {
                echo str_repeat(" ", strlenu($valueMax)-strlenu($value))."$value - $key\n";
            }
            echo "\n";
        }
    }
    
    // Show requests as graph over time
    public function showRequestsGraph($data, $text) {
        if (!is_array_empty($data)) {
            list ($terminalWidth) = $this->yellow->toolbox->detectTerminalInformation();
            $yAxis = $this->getAxisValue(max($data));
            $textYAxisMax = $yAxis<1000000 ? "$yAxis - " : ">1M - ";
            $textYAxisMid = str_repeat(" ", strlenu($textYAxisMax));
            $textYAxisMin = str_repeat(" ", strlenu($textYAxisMax)-4)."0 - ";
            $xSize = $terminalWidth<21 ? $terminalWidth : $terminalWidth-strlenu($textYAxisMax);
            $ySize = intval($this->yellow->system->get("trafficLinesMax")*2);
            $xScale = $xSize / (count($data)-1);
            $yScale = $ySize / $yAxis;
            $x1 = $y1 = $x2 = $y2 = -1;
            $this->clearGraphicsBuffer($graphicsBuffer, $xSize, $ySize);
            foreach ($data as $x=>$y) {
                $x3 = intval($x*$xScale);
                $y3 = intval($y*$yScale);
                if ($x3>=$xSize) $x3 = $xSize-1;
                if ($y3>=$ySize) $y3 = $ySize-1;
                if ($x2==-1) { $x2 = $x3; $y2 = $y3; }
                if ($x2==$x3) {
                    $y2 = max($y2, $y3);
                } else {
                    if ($x1!=-1) $this->drawGraphicsLine($graphicsBuffer, $xSize, $ySize, $x1, $y1, $x2, $y2);
                    $x1 = $x2;
                    $y1 = $y2;
                    $x2 = $x3;
                    $y2 = $y3;
                }
            }
            if ($x1!=-1) $this->drawGraphicsLine($graphicsBuffer, $xSize, $ySize, $x1, $y1, $x2, $y2);
            echo "$text\n\n";
            for ($y=$ySize-1; $y>=0; $y-=2) {
                if ($terminalWidth<21) {
                    $textYAxis = "";
                } else {
                    $textYAxis = $textYAxisMid;
                    if ($y==$ySize-1) $textYAxis = $textYAxisMax;
                    if ($y==1) $textYAxis = $textYAxisMin;
                }
                echo $textYAxis.$this->getGraphicsText($graphicsBuffer, $xSize, $ySize, $y)."\n";
            }
            echo "\n";
        }
    }
    
    // Clear graphics buffer, monochrome 1 bit per pixel
    public function clearGraphicsBuffer(&$graphicsBuffer, $xSize, $ySize) {
        if ($xSize>0 && $ySize>0) {
            $graphicsBuffer = array_fill(0, (intval($xSize/8)+1) * $ySize, 0);
        }
    }
    
    // Draw line into graphics buffer, bresenham algorithm
    public function drawGraphicsLine(&$graphicsBuffer, $xSize, $ySize, $x1, $y1, $x2, $y2) {
        $dx = abs($x2 - $x1);
        $sx = $x1<$x2 ? 1 : -1;
        $dy = -abs($y2 - $y1);
        $sy = $y1<$y2 ? 1 : -1;
        $error = $dx + $dy;
        for (;;) {
            $this->drawGraphicsPixel($graphicsBuffer, $xSize, $ySize, $x1, $y1);
            if ($x1==$x2 && $y1==$y2) break;
            $error2 = $error * 2;
            if ($error2 > $dy) { $error += $dy; $x1 += $sx; }
            if ($error2 < $dx) { $error += $dx; $y1 += $sy; }
        }
    }
    
    // Draw point into graphics buffer, automatic clipping
    public function drawGraphicsPixel(&$graphicsBuffer, $xSize, $ySize, $x, $y) {
        if ($x>=0 && $x<$xSize && $y>=0 && $y<$ySize) {
            $pos = ((intval($xSize/8)+1) * ($ySize-$y-1)) + intval($x/8);
            $graphicsBuffer[$pos] |= 0x80>>($x%8);
        }
    }
    
    // Return graphics buffer as text, UTF-8 box-drawing characters
    public function getGraphicsText($graphicsBuffer, $xSize, $ySize, $y) {
        $output = "";
        $boxDrawingUtf8 = array("\x20", "\xE2\x96\x80", "\xE2\x96\x84", "\xE2\x96\x88");
        if ($y>0 && $y<$ySize) {
            for ($x=0; $x<$xSize; ++$x) {
                $index = 0;
                $pos = ((intval($xSize/8)+1) * ($ySize-$y-1)) + intval($x/8);
                if ($graphicsBuffer[$pos] & (0x80>>($x%8))) $index += 1;
                $pos += (intval($xSize/8)+1);
                if ($graphicsBuffer[$pos] & (0x80>>($x%8))) $index += 2;
                $output .= $boxDrawingUtf8[$index];
            }
        } else {
            $output = str_repeat($boxDrawingUtf8[0], $xSize);
        }
        return $output;
    }
    
    // Check static settings
    public function checkStaticSettings() {
        return preg_match("/^(http|https):/", $this->yellow->system->get("trafficStaticUrl"));
    }
    
    // Check request arguments
    public function checkRequestArguments($method, $location, $referer) {
        return (($method=="GET" || $method=="POST") && substru($location, 0, 1)=="/" && ($referer=="-" || substru($referer, 0, 4)=="http"));
    }
    
    // Return location, decode file-encoding and URL-encoding
    public function getLocation($uri) {
        $uri = preg_replace_callback("#(\\\x[0-9a-f]{2})#", function ($matches) {
            return chr(hexdec($matches[1]));
        }, $uri);
        return rawurldecode(($pos = strposu($uri, "?")) ? substru($uri, 0, $pos) : $uri);
    }
    
    // Return referer, decode file-encoding and URL-encoding
    public function getReferer($referer, $refererSelf) {
        $referer = preg_replace_callback("#(\\\x[0-9a-f]{2})#", function ($matches) {
            return chr(hexdec($matches[1]));
        }, $referer);
        $referer = rawurldecode($referer);
        if (preg_match("#^(\w+:\/\/[^/]+)$#", $referer)) $referer .= "/";
        return preg_match("#$refererSelf#", $referer) ? "-" : $referer;
    }
    
    // Return URL
    public function getUrl($scheme, $address, $base, $location) {
        return "$scheme://$address$location";
    }

    // Return search URL, if available
    public function getUrlSearch($scheme, $address, $base, $location, $locationSearch) {
        $locationSearch = $base."(.*)".$locationSearch."query".$this->yellow->toolbox->getLocationArgumentsSeparator();
        $urlSearch = preg_match("#^$locationSearch([^/]+)/$#", $location) ? ("$scheme://$address".strtoloweru($location)) : "-";
        return str_replace(array("%", "\x1c", "\x1d", "\x1e", "\x20"), array("%25", "%1C", "%1D", "%1E", "%20"), $urlSearch);
    }
    
    // Return referring site URL, if available
    public function getUrlSite($referer) {
        list($scheme, $address, $base) = $this->yellow->lookup->getUrlInformation($referer);
        return ($scheme=="http" || $scheme=="https") ? "$scheme://$address" : "-";
    }
    
    // Return human readable status
    public function getStatusFormatted($statusCode) {
        return $this->yellow->toolbox->getHttpStatusFormatted($statusCode, true);
    }
    
    // Return value for axis
    public function getAxisValue($value) {
        if ($value<100) {
            $value = intval($value/10)*10+10;
        } elseif ($value<1000) {
            $value = intval($value/100)*100+100;
        } elseif ($value<10000) {
            $value = intval($value/1000)*1000+1000;
        } elseif ($value<100000) {
            $value = intval($value/10000)*10000+10000;
        } elseif ($value<1000000) {
            $value = intval($value/100000)*100000+100000;
        }
        return $value;
    }
    
    // Return progress in percent
    public function getProgressPercent($now, $total, $increments, $max) {
        $percent = intval(($max/$total) * $now);
        if ($increments>1) $percent = intval($percent/$increments) * $increments;
        return min($max, $percent);
    }
    
    // Return arguments from a line of log file
    public function getLineArguments($line) {
        if (preg_match("/^(\S+) (\S+) (\S+) \[(.+)\] \"(\S+) (.*?) (\S+)\" (\S+) (\S+) \"(.*?)\" \"(.*?)\"$/", $line, $matches)) {
            $data = array($matches[4], $matches[5], $matches[6], $matches[7], $matches[8], $matches[10], $matches[11]);
        } else {
            $data = array("", "", "", "", "", "", "");
        }
        return $data;
    }
    
    // Return first text line from file, empty string if not found
    public function getFileLineFirst($fileHandle) {
        $line = "";
        fseek($fileHandle, 0);
        for (;;) {
            $dataBuffer = fread($fileHandle, 4096);
            if (feof($fileHandle) || $dataBuffer===false) {
                $line = false;
                break;
            }
            if (($pos = strposu($dataBuffer, "\n"))!==false) {
                $line .= substru($dataBuffer, 0, $pos);
                break;
            }
            $line .= $dataBuffer;
        }
        return $line!==false ? $line : "";
    }

    // Return last text line from file, empty string if not found
    public function getFileLineLast($fileHandle, $fileSize) {
        $filePos = $fileSize-1;
        $fileTop = -1;
        $line = $this->getFileLinePrevious($fileHandle, $filePos, $fileTop, $dataBuffer);
        return $line!==false ? $line : "";
    }

    // Return previous text line from file, false if not found
    public function getFileLinePrevious($fileHandle, &$filePos, &$fileTop, &$dataBuffer) {
        if ($filePos>=0) {
            $line = "";
            $lineEndingSearch = false;
            $this->getFileLineBuffer($fileHandle, $filePos, $fileTop, $dataBuffer);
            $endPos = $filePos - $fileTop;
            for (;$filePos>=0; --$filePos) {
                $currentPos = $filePos - $fileTop;
                if ($dataBuffer===false) {
                    $line = false;
                    break;
                }
                if ($dataBuffer[$currentPos]=="\n" && $lineEndingSearch) {
                    $line = substru($dataBuffer, $currentPos+1, $endPos-$currentPos).$line;
                    break;
                }
                if ($currentPos==0) {
                    $line = substru($dataBuffer, $currentPos, $endPos-$currentPos+1).$line;
                    $this->getFileLineBuffer($fileHandle, $filePos-1, $fileTop, $dataBuffer);
                    $endPos =  $filePos-1 - $fileTop;
                }
                $lineEndingSearch = true;
            }
        } else {
            $line = false;
        }
        return $line;
    }
    
    // Update text line buffer
    public function getFileLineBuffer($fileHandle, $filePos, &$fileTop, &$dataBuffer) {
        if ($filePos>=0) {
            $top = intval($filePos/4096) * 4096;
            if ($fileTop!=$top) {
                $fileTop = $top;
                fseek($fileHandle, $fileTop);
                $dataBuffer = fread($fileHandle, 4096);
            }
        }
    }
}
