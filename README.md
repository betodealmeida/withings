## Withings unofficial API ##

This API is not official from Withings, you not need to create ani aplication but you need login and password. Why to not use official API? It does not provide you all information. Especially temperature and co2 data.

### How to used it? ###

First initiace class Withings:

```
$withings = new Fabulator\Withings('YOUR_EMAIL', 'YOUR_PASSWORD');
```

Than you have to get your session. You can story but it have about one hour expiration.

```
$session = $withings->getSessionKey();
```

If you known a session key, just insert it.

```
$withings->setSessionKey(YOUR_SESSION_KEY);
```

And then you can request API endopoints. Now there is only air quality and co2 getter. But you can add your own - just trace Withings dashboard.

```
$from = new \DateTime('@' . (time() - 60*60*24*2));
$to = new \DateTime();
$withings->getAirQuality($from, $to, 'YOUR_SCALE_ID')
```

How to get you scale id? Go to https://healthmate.withings.com/settings and sniff it from network ajax calls.
