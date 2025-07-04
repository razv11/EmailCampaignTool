<!DOCTYPE html>
<html>
    <head>
        <title>{{ $mailSubject  }}</title>
    </head>

    <body>
        <h1><strong>{{ $mailSubject }}</strong></h1>

        <p>{{ $mailBody }}</p>

        <hr>
        <small>Sent at {{ $mailSentAt }}</small>
    </body>
</html>
