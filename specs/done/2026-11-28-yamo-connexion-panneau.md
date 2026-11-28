Visiteur:
Page profil message d’invite: connectez-vous pour gérer vos réservations et vos logements
Page profil et page notifications on la meme structure
Titre, sous-titre, message d’invite et bouton bouton login or sign up
Faire un seul composant (skill déjà existant)

Click sur  bouton login or sign up:
Ouvre le panel avec toute sa hauteur maximale
Titre du panel:  login or sign up

Formulaire bouton login or sign up:
1. Si on a un cookie qui nous dit quel est le moyen de login utilisé lors de la dernière connexion (email, telephone):
Titre formulaire welcome back prenom nom (prenom et nom lus dans le cookie)
Sous-titre caché email ou telephone (selon dernières moyen de connexion utilisé)
Note: we may (“email“ ou “text you” selon dernier moyen) a code to log you in
Bouton login qui redirige plutôt vers un process qui va juste envoyer le code au dernier moyen de connexion 
En dessous du bouton un lien “not you” qui vers le moyen de login quand on n’a pas de cookie de dernière connexion

2. On n’a pas de cookie
On retrouve le formulaire de login actuel pour choisir email ou telephone
Le mail ou le téléphone un code est envoyé par ce canal

Donc à la sortie du point 1 ou 2, on est dans les 2 cas en attente de code qu’on aurait reçu

Formulaire pour le code:
Sous-titre: we sent a code to (email ou téléphone caché)
Champ code soumet le formulaire une fois rempli
En dessous du champ pour la saisie du code: didn’t get it ? Lien send a new code
Un lien “try another way” qui va ouvrir dans un panel avec hauteur adapté avec les liens suivants:
Get a code text via SMS (si on a pas reçu le code par mail) avec une ligne en dessous avec le telephone caché
Get a code text via email (si on a pas reçu le code par téléphone) avec une ligne en dessous avec l’email caché
Ces liens ont 2 modes:
Mode le moyen sélectionné est dans le cookie: alors le lien envoi le message/ou mail et affiche juste un message que le code au le moyen caché
Mode le moyen sélectionné n’est pas dans le cookie, redirection vers la page de login avec le bon moyen sélectionné par défaut.

Si on arrive à cette étape, ça veut dire qu’on a reçu et entré le code (mettre email_verified_at ou phone_verified_at a now si ce n’etait pas encore vérifié)

Après la validation du code on arrive à la saisie du mot de passe:
 Si l’utilisateur existe
juste champ mot de passe
lien mot de passe oublié
si l’utilisateur n’existe pas
champ mot de passe
champ confirmer mot de passe

Après la saisie du mot de passe:
Si l’utilisateur existe
il est connecté
si l’utilisateur n’existe pas
formulaire d’inscription 
nom
pre nom
telephone pas obligatoire (ajouter ce champ si l’utilisateur a choisi email)
telephone pas obligatoire (ajouter ce champ si l’utilisateur a choisi téléphone) 



Si l’utilisateur n’est pas connecté: tous les champs indicatifs, si on n’a pas l’indicatif dans les cookies, iindicatif par défaut celui du pays sélectionné. On propose l’indicatif de tous les pays pas seulement les pays actifs. 

Les pays actifs ne sont utilisés que pour la sélection du pays au départ et sur le menu de la barre du haut quand on est connecté.







