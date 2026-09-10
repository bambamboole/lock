<?php
declare(strict_types=1);

return [
    'permissions' => [
        'admin' => [
            'access' => 'Auf den Admin-Bereich zugreifen',
            'users' => [
                'manage' => 'Benutzer einsehen und verwalten',
            ],
            'realms' => [
                'view' => 'Realms ansehen',
                'manage' => 'Realms einsehen und verwalten',
            ],
            'roles' => [
                'manage' => 'Festlegen, welche Benutzer Admins sind',
            ],
        ],
    ],
    'activity' => [
        'heading' => 'Admin-Aktivität',
        'empty' => 'Noch keine Admin-Aktivität.',
        'system-actor' => 'System (Konsole)',
        'detail' => [
            'empty' => 'Keine weiteren Details erfasst.',
        ],
        'columns' => [
            'when' => 'Wann',
            'actor' => 'Akteur',
            'event' => 'Ereignis',
            'description' => 'Beschreibung',
        ],
    ],
    'users' => [
        'heading' => 'Benutzer',
        'description' => 'Benutzer von :realm.',
        'created' => 'Benutzer angelegt.',
        'updated' => 'Benutzer aktualisiert.',
        'columns' => [
            'member' => 'Mitglied',
            'verified' => 'Verifiziert',
            'mfa' => 'MFA',
            'blocked' => 'Gesperrt',
            'admin' => 'Admin',
            'created-at' => 'Beigetreten',
        ],
        'filters' => [
            'verified' => 'E-Mail verifiziert',
            'blocked' => 'Gesperrt',
            'mfa' => 'MFA aktiv',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'status' => [
            'active' => 'Aktiv',
            'blocked' => 'Gesperrt',
            'mfa-enabled' => 'Aktiviert',
            'mfa-disabled' => 'Nicht eingerichtet',
        ],
        'create' => [
            'heading' => 'Benutzer anlegen',
            'description' => 'Fügt :realm einen Benutzer hinzu. Laden Sie ihn ein, ein eigenes Passwort zu wählen, oder legen Sie jetzt eines fest.',
            'submit' => 'Benutzer anlegen',
        ],
        'fields' => [
            'credentials' => [
                'label' => 'Zugangsdaten',
                'invite' => 'Einladungs-E-Mail zum Setzen eines Passworts senden',
                'password' => 'Ein initiales Passwort festlegen',
            ],
            'email-verified' => [
                'label' => 'E-Mail verifiziert',
                'help-text' => 'Eine verifizierte Adresse überspringt die Bestätigungs-E-Mail.',
            ],
        ],
        'detail' => [
            'created-at' => 'Beigetreten',
            'blocked-at' => 'Gesperrt seit',
            'status' => 'Status',
            'mfa' => 'Mehrfaktor-Authentifizierung',
            'realm' => 'Realm',
            'realms' => [
                'heading' => 'Realm',
            ],
            'profile' => [
                'heading' => 'Profil',
                'subtitle' => 'Name, E-Mail-Adresse, Verifizierungsstatus und Realm-Zugehörigkeit.',
            ],
            'admin-roles' => [
                'heading' => 'Admin-Rollen',
            ],
            'account-actions' => [
                'heading' => 'Kontoaktionen',
            ],
            'realm-roles' => [
                'heading' => 'Realm-Rollen',
                'subtitle' => 'Werden als roles-Claim in jedem ID- und Access-Token dieses Benutzers ausgegeben.',
                'none' => 'Keine Realm-Rollen zugewiesen.',
                'define' => 'Zuerst die Rollen dieses Realms anlegen',
                'field' => [
                    'label' => 'Rollen',
                ],
                'update' => 'Realm-Rollen aktualisieren',
                'updated' => 'Realm-Rollen aktualisiert.',
            ],
            'roles' => [
                'field' => [
                    'label' => 'Globale Rollen',
                ],
            ],
            'update-roles' => 'Rollen aktualisieren',
            'roles-updated' => 'Globale Rollen aktualisiert.',
            'resend-verification' => 'Verifizierung erneut senden',
            'verification-sent' => 'Verifizierungs-E-Mail gesendet.',
            'send-password-reset' => 'Passwort-Reset senden',
            'password-reset-sent' => 'E-Mail zum Zurücksetzen des Passworts gesendet.',
            'block' => [
                'label' => 'Benutzer sperren',
                'confirm' => [
                    'title' => 'Diesen Benutzer sperren?',
                    'description' => 'Der Benutzer kann sich nicht mehr anmelden, und alle aktiven Sitzungen und Tokens werden ungültig.',
                ],
                'done' => 'Benutzer gesperrt.',
            ],
            'unblock' => [
                'label' => 'Sperre aufheben',
                'confirm' => [
                    'title' => 'Sperre dieses Benutzers aufheben?',
                    'description' => 'Der Benutzer kann sich wieder anmelden.',
                ],
                'done' => 'Sperre aufgehoben.',
            ],
            'reset-mfa' => [
                'label' => 'MFA zurücksetzen',
                'confirm' => [
                    'title' => 'Mehrfaktor-Authentifizierung zurücksetzen?',
                    'description' => 'Entfernt alle Authenticator-Apps, Wiederherstellungscodes und Passkeys. Bis zur erneuten Einrichtung meldet sich der Benutzer nur mit seinem Passwort an.',
                ],
                'done' => 'Mehrfaktor-Authentifizierung zurückgesetzt.',
            ],
            'sessions' => [
                'heading' => 'Sitzungen',
                'subtitle' => 'Anmeldesitzungen dieses Benutzers und die daran beteiligten Clients.',
                'empty' => 'Noch keine Sitzungen.',
                'not-active' => 'Diese Sitzung ist nicht mehr aktiv.',
                'columns' => [
                    'sid' => 'Sitzung',
                    'status' => 'Status',
                    'clients' => 'Clients',
                    'started-at' => 'Gestartet',
                    'expires-at' => 'Läuft ab',
                ],
                'status' => [
                    'active' => 'Aktiv',
                    'expired' => 'Abgelaufen',
                    'revoked' => 'Beendet',
                ],
                'end' => [
                    'label' => 'Beenden',
                    'confirm' => [
                        'title' => 'Diese Sitzung beenden?',
                        'description' => 'Beteiligte Clients werden aufgefordert, den Benutzer abzumelden.',
                    ],
                    'done' => 'Sitzung beendet.',
                ],
                'end-all' => [
                    'label' => 'Alle Sitzungen beenden',
                    'confirm' => [
                        'title' => 'Alle Sitzungen beenden?',
                        'description' => 'Der Benutzer wird überall abgemeldet, und Clients werden aufgefordert, ihn abzumelden.',
                    ],
                    'done' => ':count Sitzungen beendet.',
                ],
            ],
            'delete' => [
                'heading' => 'Benutzer löschen',
                'subtitle' => 'Löscht den Benutzer samt Sitzungen, Tokens, Zustimmungen und Authentifizierungsfaktoren. Das lässt sich nicht rückgängig machen.',
                'label' => 'Benutzer löschen',
                'confirm' => [
                    'title' => 'Diesen Benutzer löschen?',
                    'description' => 'Alles, was der Benutzer in diesem Realm besitzt, wird endgültig entfernt.',
                ],
                'done' => 'Benutzer gelöscht.',
            ],
        ],
        'already-verified' => 'Dieser Benutzer hat seine E-Mail-Adresse bereits verifiziert.',
        'already-blocked' => 'Dieser Benutzer ist bereits gesperrt.',
        'not-blocked' => 'Dieser Benutzer ist nicht gesperrt.',
    ],
    'clients' => [
        'heading' => 'Clients',
        'description' => 'OIDC-Clients von :realm.',
        'created' => 'Client angelegt.',
        'updated' => 'Client aktualisiert.',
        'deleted' => 'Client gelöscht.',
        'columns' => [
            'name' => 'Name',
            'client-id' => 'Client-ID',
            'type' => 'Typ',
            'grant-types' => 'Grant-Typen',
            'status' => 'Status',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'status' => [
            'active' => 'Aktiv',
            'blocked' => 'Gesperrt',
        ],
        'type-badges' => [
            'public' => 'Öffentlich',
            'confidential' => 'Vertraulich',
        ],
        'types' => [
            'none' => 'Öffentlich (kein Secret, nur PKCE)',
            'client-secret-basic' => 'Vertraulich (client_secret_basic)',
            'client-secret-post' => 'Vertraulich (client_secret_post)',
        ],
        'grant-types' => [
            'authorization-code' => 'Authorization Code (PKCE)',
            'refresh-token' => 'Refresh Token',
            'client-credentials' => 'Client Credentials',
            'personal-access' => 'Persönlicher Zugriff',
        ],
        'create' => [
            'heading' => 'Client anlegen',
            'description' => 'Der Client wird in :realm mit den Standard- und optionalen Scopes des Realms angelegt.',
            'submit' => 'Client anlegen',
        ],
        'sections' => [
            'general' => 'Client',
        ],
        'fields' => [
            'name' => [
                'label' => 'Name',
            ],
            'type' => [
                'label' => 'Client-Typ',
                'help-text' => 'Vertrauliche Clients authentifizieren sich am Token-Endpunkt mit einem Secret; öffentliche Clients verlassen sich allein auf PKCE.',
            ],
            'grant-types' => [
                'label' => 'Grant-Typen',
                'help-text' => 'Refresh Tokens setzen den Authorization-Code-Grant voraus. Client Credentials setzen einen vertraulichen Client voraus.',
            ],
            'redirect-uris' => [
                'label' => 'Redirect-URIs',
                'help-text' => 'Eine absolute URI pro Zeile. Für den Authorization-Code-Grant erforderlich.',
            ],
            'post-logout-redirect-uris' => [
                'label' => 'Post-Logout-Redirect-URIs',
                'help-text' => 'Eine absolute URI pro Zeile. Wohin der Client Benutzer nach dem Abmelden schicken darf.',
            ],
            'consent-required' => [
                'label' => 'Einwilligung erforderlich',
                'help-text' => 'Benutzer müssen die angeforderten Scopes bestätigen, bevor Tokens ausgestellt werden.',
            ],
            'backchannel-logout-uri' => [
                'label' => 'Back-Channel-Logout-URI',
                'help-text' => 'Endpunkt, der ein Logout-Token erhält, wenn die Sitzung des Benutzers endet.',
            ],
        ],
        'validation' => [
            'refresh-token-needs-code' => 'Der Refresh-Token-Grant setzt den Authorization-Code-Grant voraus.',
            'client-credentials-needs-secret' => 'Der Client-Credentials-Grant setzt einen vertraulichen Client voraus.',
            'redirect-uri-required' => 'Für den Authorization-Code-Grant ist mindestens eine Redirect-URI erforderlich.',
            'invalid-uri' => 'Die URI :uri muss absolut sein, Schema und Host enthalten und kein Fragment haben.',
        ],
        'detail' => [
            'description' => 'Client von :realm.',
            'created-at' => 'Erstellt',
            'scopes' => 'Scopes',
            'summary' => [
                'heading' => 'Übersicht',
            ],
            'secret' => [
                'heading' => 'Client-Secret',
                'subtitle' => 'Kopieren Sie das Secret jetzt. Es wird nur einmal angezeigt und kann nicht wiederhergestellt werden.',
                'label' => 'Client-Secret',
            ],
            'settings' => [
                'heading' => 'Einstellungen',
                'subtitle' => 'Name, Typ, Grant-Typen und URIs des Clients.',
            ],
            'credentials' => [
                'heading' => 'Zugangsdaten',
                'subtitle' => 'Das Rotieren des Secrets macht das aktuelle sofort ungültig.',
            ],
            'danger' => [
                'heading' => 'Gefahrenzone',
                'subtitle' => 'Ein gesperrter Client erhält keine neuen Tokens, bis er entsperrt wird. Löschen entfernt seine Tokens, Einwilligungen und Sitzungen.',
            ],
            'rotate-secret' => [
                'label' => 'Secret rotieren',
                'done' => 'Client-Secret rotiert.',
                'confirm' => [
                    'title' => 'Client-Secret rotieren?',
                    'description' => 'Das aktuelle Secret funktioniert sofort nicht mehr. Das neue wird einmalig angezeigt.',
                ],
            ],
            'revoke' => [
                'label' => 'Client sperren',
                'done' => 'Client gesperrt.',
                'confirm' => [
                    'title' => 'Diesen Client sperren?',
                    'description' => 'Der Client kann keine Anmeldungen mehr starten und keine Tokens mehr erhalten.',
                ],
            ],
            'restore' => [
                'label' => 'Client entsperren',
                'done' => 'Client entsperrt.',
                'confirm' => [
                    'title' => 'Diesen Client entsperren?',
                    'description' => 'Der Client kann wieder Anmeldungen starten und Tokens erhalten.',
                ],
            ],
            'delete' => [
                'label' => 'Client löschen',
                'confirm' => [
                    'title' => 'Diesen Client löschen?',
                    'description' => 'Löscht den Client samt Tokens, Einwilligungen und Sitzungen. Das lässt sich nicht rückgängig machen.',
                ],
            ],
        ],
    ],
    'realms' => [
        'heading' => 'Realms',
        'description' => 'Jeder Realm ist eine eigene Identitätsgrenze mit eigenen Benutzern, Clients und Authentifizierungseinstellungen.',
        'columns' => [
            'name' => 'Name',
            'slug' => 'Slug',
            'users' => 'Benutzer',
            'created-at' => 'Erstellt',
        ],
        'actions' => [
            'manage' => 'Verwalten',
        ],
        'created' => 'Realm angelegt.',
        'updated' => 'Realm aktualisiert.',
        'configuration-saved' => 'Realm-Konfiguration gespeichert.',
        'deleted' => 'Realm gelöscht.',
        'settings' => [
            'heading' => 'Realm-Einstellungen',
            'description' => 'Name, Authentifizierungskonfiguration und Löschung von :realm.',
        ],
        'tabs' => [
            'general' => 'Allgemein',
            'danger' => 'Gefahrenzone',
        ],
        'values' => [
            'never' => 'Nie',
            'generated' => 'Pro Realm generiert',
        ],
        'summary' => [
            'users' => 'Benutzer',
            'created-at' => 'Erstellt',
        ],
        'general' => [
            'heading' => 'Realm-Details',
            'subtitle' => 'Öffnen Sie eine Zeile, um sie zu ändern. Die Kennung bleibt dauerhaft fest.',
            'submit' => 'Realm speichern',
        ],
        'danger' => [
            'heading' => 'Realm löschen',
            'subtitle' => 'Löscht den Realm samt Benutzern, Clients, Sitzungen und Tokens. Das lässt sich nicht rückgängig machen.',
            'confirm-name' => 'Realm-Namen bestätigen',
            'name-mismatch' => 'Der Realm-Name stimmt nicht überein.',
            'submit' => 'Realm löschen',
        ],
        'create' => [
            'heading' => 'Realm anlegen',
            'description' => 'Neue Realms starten mit den Standardeinstellungen für die Authentifizierung. Nach dem Anlegen können Sie diese konfigurieren.',
            'submit' => 'Realm anlegen',
        ],
        'sections' => [
            'general' => 'Allgemein',
            'tokens' => 'Token-Laufzeiten',
            'sessions' => 'Sitzungen',
            'mfa' => 'Mehrfaktor-Authentifizierung',
            'passwords' => 'Passwörter',
            'clients' => 'Client-Regeln',
        ],
        'factors' => [
            'totp' => 'Authenticator-App (TOTP)',
            'webauthn' => 'Sicherheitsschlüssel oder Passkey (WebAuthn)',
        ],
        'fields' => [
            'name' => [
                'label' => 'Realm-Name',
            ],
            'slug' => [
                'label' => 'Realm-Kennung',
                'help-text' => 'Wird in den Realm-URLs verwendet. Nur Kleinbuchstaben, Zahlen und einzelne Bindestriche. Später nicht mehr änderbar.',
            ],
            'access-token-lifetime' => [
                'label' => 'Laufzeit von Access-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter Access-Tokens in Sekunden.',
            ],
            'id-token-lifetime' => [
                'label' => 'Laufzeit von ID-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter ID-Tokens in Sekunden.',
            ],
            'client-credentials-lifetime' => [
                'label' => 'Laufzeit von Machine-to-Machine-Tokens',
                'help-text' => 'Laufzeit von Client-Credentials-Tokens in Sekunden.',
            ],
            'refresh-token-lifetime' => [
                'label' => 'Laufzeit von Refresh-Tokens',
                'help-text' => 'Laufzeit neu ausgestellter Refresh-Tokens in Sekunden.',
            ],
            'session-cookie-name' => [
                'label' => 'Name des Session-Cookies',
                'help-text' => 'Leer lassen, um einen eigenen Cookie-Namen für diesen Realm zu erzeugen. Nach einer Änderung ist eine erneute Anmeldung erforderlich.',
            ],
            'session-absolute-lifetime' => [
                'label' => 'Maximale Sitzungsdauer',
                'help-text' => 'Maximales Alter einer Authentifizierungssitzung in Sekunden.',
            ],
            'session-token-ttl' => [
                'label' => 'Laufzeit des Sitzungstokens',
                'help-text' => 'Laufzeit des zu einer Sitzung gehörenden Tokens in Sekunden.',
            ],
            'session-token-refresh-skew' => [
                'label' => 'Erneuerungsfenster für Sitzungstokens',
                'help-text' => 'Anzahl der Sekunden vor Ablauf, ab der ein Sitzungstoken erneuert wird.',
            ],
            'challenge-providers' => [
                'label' => 'Verfügbare zweite Faktoren',
                'help-text' => 'Faktoren, mit denen Benutzer eine Mehrfaktor-Prüfung abschließen können.',
            ],
            'totp-secret-length' => [
                'label' => 'Länge des TOTP-Geheimnisses',
                'help-text' => 'Länge neu erzeugter Authenticator-Geheimnisse: 16 bis 64 Zeichen in Schritten von 8.',
            ],
            'totp-window' => [
                'label' => 'TOTP-Zeittoleranz',
                'help-text' => 'Akzeptierte Zeitabweichung in Schritten von 30 Sekunden.',
            ],
            'recovery-codes' => [
                'label' => 'Anzahl der Wiederherstellungscodes',
                'help-text' => 'Anzahl der Codes beim Erstellen neuer Wiederherstellungscodes.',
            ],
            'password-min-length' => [
                'label' => 'Mindestlänge des Passworts',
                'help-text' => 'Zeichen, die ein neues Passwort mindestens haben muss.',
            ],
            'password-history' => [
                'label' => 'Passwort-Historie',
                'help-text' => 'Frühere Passwörter, die ein neues Passwort nicht wiederholen darf, einschließlich des aktuellen. 0 deaktiviert die Prüfung.',
            ],
            'password-max-age-days' => [
                'label' => 'Maximales Passwortalter',
                'help-text' => 'Tage, nach denen ein Passwort als abgelaufen gilt. 0 bedeutet, dass Passwörter nie ablaufen.',
            ],
            'password-mixed-case' => [
                'label' => 'Groß- und Kleinbuchstaben verlangen',
                'help-text' => 'Ein neues Passwort muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
            ],
            'password-numbers' => [
                'label' => 'Ziffer verlangen',
                'help-text' => 'Ein neues Passwort muss mindestens eine Ziffer enthalten.',
            ],
            'password-symbols' => [
                'label' => 'Sonderzeichen verlangen',
                'help-text' => 'Ein neues Passwort muss mindestens ein Sonderzeichen enthalten.',
            ],
            'password-uncompromised' => [
                'label' => 'Geleakte Passwörter ablehnen',
                'help-text' => 'Passwörter ablehnen, die in einem öffentlichen Datenleck gefunden wurden. Beim Setzen eines Passworts wird dafür die Range-API von Have I Been Pwned abgefragt.',
            ],
            'dynamic-registration' => [
                'label' => 'Dynamische Client-Registrierung erlauben',
                'help-text' => 'Anwendungen dürfen sich über den Registrierungsendpunkt des Realms anmelden.',
            ],
            'token-exchange' => [
                'label' => 'Token-Austausch erlauben',
                'help-text' => 'Clients dürfen Tokens innerhalb dieses Realms austauschen.',
            ],
            'first-party-trusted' => [
                'label' => 'Dem First-Party-Client vertrauen',
                'help-text' => 'Zustimmung für den First-Party-Client überspringen, sofern einer eingerichtet ist.',
            ],
            'allowed-redirect-schemes' => [
                'label' => 'Erlaubte eigene Weiterleitungsschemata',
                'help-text' => 'Ein Schema pro Zeile, ohne Doppelpunkt. Leer lassen, um nur HTTP und HTTPS zu erlauben.',
            ],
            'allowed-redirect-domains' => [
                'label' => 'Erlaubte Weiterleitungsdomains',
                'help-text' => 'Ein exakter Host pro Zeile. Mit * alle Hosts erlauben. Eine leere Liste erlaubt keine HTTP- oder HTTPS-Weiterleitungshosts.',
            ],
            'default-scopes' => [
                'label' => 'Standard-Scopes',
                'help-text' => 'Scopes, die jeder neue Client erhält und ohne Anfrage gewährt bekommt, ein Scope pro Zeile.',
            ],
            'optional-scopes' => [
                'label' => 'Optionale Scopes',
                'help-text' => 'Scopes, die jeder neue Client anfragen darf, ein Scope pro Zeile. Ein einzelnes * erlaubt jeden Scope aus dem Katalog.',
            ],
            'trusted-clients' => [
                'label' => 'Vertrauenswürdige Clients',
                'help-text' => 'Eine Client-Kennung aus diesem Realm pro Zeile. Diese Clients überspringen die Zustimmung.',
            ],
        ],
    ],
    'roles' => [
        'heading' => 'Rollen',
        'description' => 'Realm-Rollen von :realm, ausgegeben als roles-Claim in jedem ID- und Access-Token.',
        'created' => 'Rolle angelegt.',
        'updated' => 'Rolle aktualisiert.',
        'deleted' => 'Rolle gelöscht.',
        'columns' => [
            'name' => 'Name',
            'description' => 'Beschreibung',
            'users' => 'Benutzer',
        ],
        'create' => [
            'label' => 'Rolle anlegen',
        ],
        'edit' => [
            'label' => 'Bearbeiten',
        ],
        'delete' => [
            'label' => 'Löschen',
            'confirm' => [
                'title' => 'Diese Rolle löschen?',
                'description' => 'Die Rolle wird allen Benutzern entzogen, die sie tragen. Bereits ausgestellte Tokens behalten sie bis zum Ablauf.',
            ],
        ],
        'fields' => [
            'name' => [
                'label' => 'Name',
                'help-text' => 'Der Wert, den Relying Parties im roles-Claim erhalten, zum Beispiel admin oder member.',
            ],
            'description' => [
                'label' => 'Beschreibung',
            ],
        ],
        'validation' => [
            'name-format' => 'Der Name darf nur Buchstaben, Ziffern und die Zeichen _ . : - enthalten und muss mit einem Buchstaben oder einer Ziffer beginnen.',
        ],
    ],
];
