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
    <title>Votre réponse — {{ $guest->event->name }}</title>
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
                <p style="margin:0 0 24px;font-size:16px;line-height:24px;">
                    Votre réponse a bien été enregistrée. En voici le récapitulatif.
                </p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:15px;line-height:22px;">
                    <tr>
                        <td style="padding:6px 0;color:#66554a;">Présence</td>
                        <td style="padding:6px 0;text-align:right;font-weight:600;">{{ $guest->rsvp_status->label() }}</td>
                    </tr>
                    @if ($guest->rsvp_status === \App\Enums\RsvpStatus::Attending)
                        <tr>
                            <td style="padding:6px 0;color:#66554a;">Personnes</td>
                            <td style="padding:6px 0;text-align:right;">{{ $guest->rsvp_seats }}</td>
                        </tr>
                        @if ($guest->attendee_names)
                            <tr>
                                <td style="padding:6px 0;color:#66554a;">Prénoms</td>
                                <td style="padding:6px 0;text-align:right;">{!! nl2br(e($guest->attendee_names)) !!}</td>
                            </tr>
                        @endif
                        @if ($guest->dietary_notes)
                            <tr>
                                <td style="padding:6px 0;color:#66554a;">Régime</td>
                                <td style="padding:6px 0;text-align:right;">{{ $guest->dietary_notes }}</td>
                            </tr>
                        @endif
                    @endif
                    @if ($guest->rsvp_message)
                        <tr>
                            <td style="padding:6px 0;color:#66554a;">Message</td>
                            <td style="padding:6px 0;text-align:right;">{!! nl2br(e($guest->rsvp_message)) !!}</td>
                        </tr>
                    @endif
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
                </table>

                @if ($guest->rsvp_is_late)
                    <p style="margin:16px 0 0;font-size:13px;line-height:18px;color:#8a5a00;">
                        Cette réponse a été transmise après la date limite.
                    </p>
                @endif

                <p style="margin:24px 0 0;">
                    <a href="{{ $guest->invitationUrl() }}" style="display:inline-block;min-height:44px;padding:12px 20px;background-color:{{ $accent }};color:#fffaf2;font-size:14px;font-weight:600;text-decoration:none;">
                        Revoir l'invitation
                    </a>
                </p>

                <p style="margin:24px 0 0;font-size:13px;line-height:18px;color:#66554a;">
                    Ce lien est personnel et confidentiel.
                </p>
                <p style="margin:8px 0 0;font-size:13px;line-height:18px;color:#66554a;">
                    Hub Événement — Abidjan, Côte d'Ivoire
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
