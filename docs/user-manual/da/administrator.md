# Administrator

Generelle indstillinger for F2-modulet laves under Administration > Indstillinger > OS2Forms F2
(`/admin/os2forms_f2/settings`):

![F2-indstillinger](./images/generated/administrator/f2-settings-empty.png)

## F2-api

Under F2-api konfigureres forbindelsen til F2's api:

![F2-api-indstillinger](./images/generated/administrator/f2-settings-empty-f2-api.png)

Passende værdier for "Url", "Bruger", "Hemmelighed" og "F2-brugernavn" kan udleveres af en F2-administrator, fx

![Udfyldte F2-indstillinger](./images/generated/administrator/f2-settings-filled-f2-api.png)

Når F2-api-indstillingerne er gemt kan man tjekke at der er hul igennem til api'et ved at trykke på "Tjek forbindelse
til api":

![Fejl under forbindelse til F2-api](./images/generated/administrator/f2-settings-api-pinged-error.png)

Når man indsætter gyldige og korrekte værdier skulle der gerne være hul igennem til api'et:

![Forbindelse til F2-api oprettet](./images/generated/administrator/f2-settings-api-pinged-success.png)

## Generelt

Under Generelt kan en administrator sætte standardindstillinger for F2-behandlere og styre om en formularbygger skal
kunne overskrive en standardindstilling.

![F2-api-general](./images/generated/administrator/f2-settings-empty-general.png)

Restriktive, men fornuftige, standardindstillinger kan fx være

![F2-api-general-filled](./images/generated/administrator/f2-settings-filled-general.png)

En formularbygger vil så ikke skulle forholde sig til om der skal arkiveres på en sag eller på en akt, og akter der
oprettes får de egenskaber (fx type og status) som administratoren har sat.
