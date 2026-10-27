@php
    $accent = match ($guest->event->type->value) {
        'bapteme' => '#42698f',
        'anniversaire' => '#a0432a',
        'fete' => '#7a3d6f',
        default => '#4e6b48',
    };
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Petit rappel — {{ $guest->event->name }}</title>
</head>
<body style="margin:0;padding:24px;background-color:#fbf6ee;color:#2a1f17;font-family:'DM Sans',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background-color:#fffaf2;border:1px solid #d9c9b0;">
        <tr>
            <td style="padding:32px;">
                <p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#66554a;">
                    {{ $guest->event->type->label() }}
                </p>
                <h1 style="margin:0 0 16px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:36px;font-weight:500;color:#2a1f17;">
                    {{ $guest->event->name }}
                </h1>

                <p style="margin:0 0 16px;font-size:16px;line-height:24px;">Bonjour {{ $guest->name }},</p>
                <p style="margin:0 0 16px;font-size:16px;line-height:24px;">
                    Nous n'avons pas encore reçu votre réponse pour cet événement.
                    Votre place est réservée, dites-nous simplement si vous serez présent.
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:22px;">
                    <tr>
                        <td style="padding:6px 0;color:#66554a;">Date</td>
                        <td style="padding:6px 0;text-align:right;">
                            {{ $guest->event->starts_at->translatedFormat('l j F Y') }}
                            à {{ $guest->event->starts_at->format('H\hi') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:6px 0;color:#66554a;">Lieu</td>
                        <td style="padding:6px 0;text-align:right;">{{ $guest->event->venue_name }}</td>
                    </tr>
                    @if ($guest->event->rsvp_deadline)
                        <tr>
                            <td style="padding:6px 0;color:#66554a;">Réponse attendue</td>
                            <td style="padding:6px 0;text-align:right;">
                                avant le {{ $guest->event->rsvp_deadline->translatedFormat('j F Y') }}
                            </td>
                        </tr>
                    @endif
                </table>

                <p style="margin:24px 0 0;">
                    <a href="{{ $guest->invitationUrl() }}" style="display:inline-block;min-height:44px;padding:12px 20px;background-color:{{ $accent }};color:#fffaf2;font-size:14px;font-weight:600;text-decoration:none;">
                        Répondre à l'invitation
                    </a>
                </p>

                <p style="margin:24px 0 0;font-size:13px;line-height:18px;color:#66554a;">
                    Ce lien est personnel et confidentiel. Vous recevez ce message car vous n'avez pas encore répondu.
                </p>
                <p style="margin:8px 0 0;font-size:13px;line-height:18px;color:#66554a;">
                    <a href="{{ $unsubscribeUrl }}" style="color:#66554a;text-decoration:underline;">
                        Ne plus recevoir de relances pour cet événement
                    </a>
                </p>
                <p style="margin:8px 0 0;font-size:13px;line-height:18px;color:#66554a;">
                    Hub Événement — Abidjan, Côte d'Ivoire
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
