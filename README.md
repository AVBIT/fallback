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
# php fallback.php --host=example.com -c=100
  Sat, 31 Mar 18 11:19:27 +0000 FALLBACK: Process has died! restarting...
  Sat, 31 Mar 18 11:19:27 +0000 FALLBACK: example.com:80 - OK (time: 0.192442 sec.)
  Sat, 31 Mar 18 11:19:29 +0000 FALLBACK: example.com:80 - OK (time: 0.186559 sec.)
  Sat, 31 Mar 18 11:19:30 +0000 FALLBACK: example.com:80 - OK (time: 0.220498 sec.)
  Sat, 31 Mar 18 11:19:32 +0000 FALLBACK: example.com:80 - OK (time: 1.321489 sec.)
  Sat, 31 Mar 18 11:19:33 +0000 FALLBACK: example.com:80 - OK (time: 0.194539 sec.)
  Sat, 31 Mar 18 11:19:35 +0000 FALLBACK: example.com:80 - OK (time: 0.183689 sec.)
  Sat, 31 Mar 18 11:19:36 +0000 FALLBACK: example.com:80 - OK (time: 0.186502 sec.)
  ^CSat, 31 Mar 18 11:19:36 +0000 FALLBACK: got signal 2 and will exit.
  Sat, 31 Mar 18 11:19:36 +0000 FALLBACK: example.com:80 STATISTICS: 7 total, 7 responses, 0 (0%) timeouts, response time min/avg/max 0.183689/0.355103/1.321489 sec.

````

Or so...

/etc/crontab
``` sh
# Fallback - provide start-up (and restart the script in case of death)
*/2     *       *       *       *       root            cd /path/to && php fallback.php --host='www.example.com' --timeout=30 --sleep=5 --count=3600 --mail='user1@example.com user2@example.com userN@example.com' >> /var/log/fallback.log
```
/etc/newsyslog.conf
``` sh
# Fallback - rotation of the log file
/var/log/fallback.log 		    root:wheel      644  10    1024  *     JC
```

