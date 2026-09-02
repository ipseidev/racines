<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Routes famille
|--------------------------------------------------------------------------
|
| Servies sur le domaine court des liens. Lecture seule, jetons distincts de
| ceux du narrateur (doc 04 §12).
|
|   /l/{token}   écoute d'un projet ou d'une histoire  (listen_project, listen_story)
|   /q/{token}   page atteinte par un QR imprimé       (jeton qr)
|   /a/{token}   action en un tap de l'Initiateur·rice (jeton action)
|   /x/{token}   téléchargement d'un export            (jeton export)
|
| Aucune histoire n'est servie ici sans passer par VisibleStoriesForFamilyMember.
| Les routes arrivent aux blocs 03, 08, 09, 13 et 14.
|
*/
