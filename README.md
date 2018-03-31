## FALLBACK
Command line tools for checking the availability and response time of any service running on TCP, with sending a mail notification when changing status.

#### THE BEER-WARE LICENSE:
> This project is licensed under the "THE BEER-WARE LICENSE":
> As long as you retain this notice you can do whatever you want with this stuff.
> If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.


#### Command line options:
          --help         Show this message
     -h   --host         Ip-address or domain name (required)
     -p   --port         Port number (optional, default: 80)
     -t   --timeout      Maximum response time (optional, default: 20, seconds)
     -s   --sleep        Sleep time after a successful check (optional, default: 1, seconds)
     -c   --count        Stop after count of connection attempts (optional, default: 0, operate until interrupted). 
     -m   --mail         Notification email or emails separated pattern /[\s,;|]+/ (optional)

#### Basic usage:

````
# php fallback.php --host=www.example.com
  Sat, 31 Mar 18 00:18:54 +0300 FALLBACK: Process has died! restarting...
  Sat, 31 Mar 18 00:18:54 +0300 FALLBACK: www.example.com:80 - OK (time: 0.183406 sec.)
  Sat, 31 Mar 18 00:18:55 +0300 FALLBACK: www.example.com:80 - OK (time: 0.185397 sec.)
  Sat, 31 Mar 18 00:18:56 +0300 FALLBACK: www.example.com:80 - OK (time: 0.185805 sec.)
  Sat, 31 Mar 18 00:18:57 +0300 FALLBACK: www.example.com:80 - OK (time: 0.185418 sec.)
  Sat, 31 Mar 18 00:18:59 +0300 FALLBACK: www.example.com:80 - OK (time: 0.185886 sec.)
  Sat, 31 Mar 18 00:19:00 +0300 FALLBACK: www.example.com:80 - OK (time: 0.183130 sec.)
  Sat, 31 Mar 18 00:19:01 +0300 FALLBACK: www.example.com:80 - OK (time: 0.184344 sec.)
  Sat, 31 Mar 18 00:19:02 +0300 FALLBACK: www.example.com:80 - OK (time: 0.183470 sec.)
  Sat, 31 Mar 18 00:19:03 +0300 FALLBACK: www.example.com:80 - OK (time: 0.184117 sec.)
  ^CSat, 31 Mar 18 00:19:04 +0300 FALLBACK: got signal 2 and will exit.
````

Or so...

/etc/crontab
``` sh
# Fallback - provide start-up (and restart the script in case of death)
*/10     *       *       *       *       root            cd /path/to && php fallback.php --host='www.example.com' --timeout=30 --sleep=10 --mail='user1@example.com user2@example.com userN@example.com' >> /var/log/fallback.log
```
/etc/newsyslog.conf
``` sh
# Fallback - rotation of the log file
/var/log/fallback.log 		    root:wheel      644  10    1024  *     JC
```

