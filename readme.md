# TYPO3 Extension nkc_address

Die Extension stellt PlugIns bereit, um Inhalte der Nordkirche API (Personen und Institutionen) auf einer Website darzustellen.

* Personen
  * Listenansicht
  * Detailansicht (Visitenkarte)
  * Suchformular
  * Helper: Weiterleitung per ID auf die Visitenkarte
  * 
* Institutionen
  * Listenansicht / Suchergebnis
  * Detailansicht (Visitenkarte)
  * Suchformular
  * Helper: Weiterleitung per ID auf die Visitenkarte

* Kartdarstellung
  * mit Liste unter der Karte
  * alleinstehend ohne Liste


## Abhängigkeiten
Diese Extension basiert auf

    nordkirche/nkc_base ^12.4
    fluidtypo3/vhs ^6.1 || ^7.0
    TYPO3 ^12.4

## Installation
Die Installation der Extension erfolgt über composer, da bei dieser Installation auch alle Abhängigkeiten mit installiert werden müssen.

    composer req nordkirche/nkc-address

Bitte binden Sie anschließend das statische Template der Extension in Ihr TypoScript Template ein.

## Konfiguration

Bitte beachten Sie die Dokumentation von nordkirche/nkc-base, um Zugriffe auf die NAPI zu ermöglichen.

Es gibt im statischen TypoScript umfangreiche Konfigurationen, die für die eigenen Bedürfnisse angepasst werden können und müssen (z.B. Pfade Icons für die Kartendarstellung) Für TYPO3 Integratoren sollten sich die meisten Dinge von selbst erklären.

Grunsätzlich ist es so, dass Konfigurationen teilweise sowohl in TypoScript als auch in den Plug-Ins möglich sind. Hier zu beachten, dass Plug-In Konfigurationen TypoScript überschreiben, wenn sie einen Wert haben.

Die Templates der Extension haben ein sehr rudimentäres Markup, um die Möglichkeiten der Extension zu zeigen. Die darzustellenen Inhalte sind so komplex, dass ein Standard-Layout wenig Sinn ergeben hätte.

## PSR-14 Events
Es gibt PSR-14 Events, um die NAPI Queries und die Ausgabe der Daten anzupassen:

| Controller            | Action      | Event                                       | Daten holen             | Daten überschreiben     |
|-----------------------|-------------|---------------------------------------------|-------------------------|-------------------------|
| InstitutionController | listAction  | ModifyAssignedListValuesForInstitutionEvent | getAssignedListValues() | setAssignedListValues() |
| InstitutionController | listAction  | ModifyInstitutionQueryEvent                 | getInstitutionQuery()   | setInstitutionQuery()   |
| InstitutionController | showAction  | ModifyAssignedValuesForInstitutionEvent     | getAssignedValues()     | setAssignedValues()     |
| PersonController      | listAction  | ModifyAssignedListValuesForPersonEvent      | getAssignedListValues() | setAssignedListValues() |
| PersonController      | listAction  | ModifyPersonQueryEvent                      | getPersonQuery()        | setPersonQuery()        |
| PersonController      | showAction  | ModifyAssignedValuesForPersonEvent          | getAssignedValues()     | setAssignedValues()     |


## Wichtige Hinweise
Bitte stellen Sie sicher, dass in der TYPO3-Konfiguration die Debug Option deaktiviert ist:

    $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = 0;

Andernfalls wird bei einer JSON Responses von TYPO3 ein Cache Hinweis angefügt und die Karten-Marker können nicht nachgeladen werden.

Wenn statische Google Karten in der Listenansicht generiert werden sollen, muss in TYPO3 folgende TypoScript-Konfiguration vorliegen:

    config.forceAbsoluteUrls = 1

Damit wird sichergestellt, dass die Icons, die bei EXT:nkc_base mitgeliefert werden, über den Asset Ordner verknüpft werden.  

## Fehler gefunden?
Bitte melden Sie Fehler via github
https://github.com/Nordkirche/nkc-address
