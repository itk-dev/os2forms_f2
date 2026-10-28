# Formularbygger

F2-behandleren kan arkivere indholdet i et vedhæftningselement (OS2Forms Attachment) som et dokument i F2. Behandleren
indsættes ligesom andre behandlere

![Tilføj F2-formularbehandler](./images/generated/form-builder/webform-handler-select-f2.png)

og konfigureres på passende vis.

![Formularbehandler](./images/generated/form-builder/webform-handler-add-general.png)

De afgørende indstillinger for F2-behandleren ligger under "F2-arkiveringsindstillinger":

![F2-arkiveringsindstillinger](./images/generated/form-builder/webform-handler-add-general-archive-settings.png)

Vælg et vedhæftningselement (af typen "OS2Forms Attachment") og angiv en titel på dokumentet der arkiveres i F2:

![Udfyldte arkiveringsindstillinger](./images/generated/form-builder/webform-handler-add-general-archive-settings-filled.png)

Under "Arkivér på" kan man vælge "[Sag](#arkivering-på-sag)" eller "[Akt](#arkivering-på-akt)" og valget har indflydelse
på yderligere muligheder i behandlerindstillingerne.

## Arkivering på sag

Som bekendt ligger dokumenter i F2 på en akt, og for at arkivere på en sag opretter behandleren derfor indledningsvist
en akt som dokumentet kan knyttes til. For at arkivere på en sag skal man angive et id der entydigt udpeger en sag i F2.
Det vil være oplagt at bruge et sags-nummer

![Arkivér på sag](./images/generated/form-builder/webform-handler-add-archive-on-case.png)

![Udfyldt "Arkivér på sag"](./images/generated/form-builder/webform-handler-add-archive-on-case-filled.png)

Når man gemmer tjekker systemet at sags-id'et entydigt identificerer en sag i F2:

![Ugyldigt sags-id](./images/generated/form-builder/webform-handler-add-archive-on-case-filled-invalid-case-identifier.png)

Hvis id'et entydigt identificerer en sag i F2, så har man med succes tilføjet en F2-behandler:

![Gyldigt og korrekt sags-id](./images/generated/form-builder/webform-handler-add-archive-on-case-filled-valid-case-identifier.png)

!["Arkivér på sag"-behandler tilføjet](./images/generated/form-builder/webform-handler-added.png)

Hvis man, som antydet, redigerer en F2-behandler kan man se detaljer om hvilken sag der faktisk arkiveres på:

![Detaljer om sag](./images/generated/form-builder/webform-handler-archive-on-case-case-details.png)

## Arkivering på akt

Når man vil arkivere på en (eksisterende) akt skal man angive et id, fx akt-nummer, der entydigt udpeger akten i F2.

![Arkivér på akt](./images/generated/form-builder/webform-handler-add-archive-on-matter.png)

Når man gemmer tjekker systemet at akt-id'et entydigt identificerer en akt i F2:

![Ugyldigt sags-id](./images/generated/form-builder/webform-handler-add-archive-on-matter-filled-invalid-matter-identifier.png)

Hvis id'et entydigt identificerer en sag i F2, så har man med succes tilføjet en F2-behandler:

!["Arkivér på akt"-behandler tilføjet](./images/generated/form-builder/webform-handler-added-2.png)

Når man redigerer behandleren, kan man se detaljer om den akt der arkiveres på:

![Detaljer om akt](./images/generated/form-builder/webform-handler-archive-on-matter-matter-details.png)

## Avancerede indstillinger

På fanen "Avanceret" kan man styre hvornår behandleren skal arkivere i F2:

![Avancerede indstillinger for F2-behandler](./images/generated/form-builder/webform-handler-add-advanced.png)

> [!NOTE]
> Standardindstillingen, "Kør når … besvarelse afsluttes", vil oftest være det rigtige valg og kun i sjældne tilfælde
> vil det være nødvendigt at vælge noget andet.
