# Le site de la kfet de l'ENSIM !

Voici le site de la Kfet de l'ENSIM !

Ce site permet de commander des aliments la kfet pour les clients grâce à un système de rechargement de comptes gérés par les baristas.

Les clients vont d'abord payer par carte puis les baristas vont recharger leur compte avec un montant.

Le client peut ensuite commander et sa commande sera validé s'il a assez d'argent.

Le problème est qu'il y a beaucoup de vols donc le but de ce projet est de rajouter un système de gestion de code et de validation de commande par QR code.

# Déploiement

Pour déployer le site en local, il faut tout d'abord cloner ce dépot sur sa machine et avoir Docker d'installé.

Il faut ensuite exécuter la commande docker compose up -d --build pour build le projet.

Le site va se trouver à l'adresse http://localhost:8001

Un compte test a été créé :

identifient : 1
mot de passe : admin

Pour visualiser la base de données il faut aller à l'adresse http://localhost:8003.

identifient : admin9988
mot de passe : ensim1995
