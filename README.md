# BilemoAPI
web service exposant une API

Instruction à suivre pour mettre en place le projet en local chez soit : 

- cloner le projet avec git en copiant l'URL du repository. 
`git clone https://github.com/AurelienDemblans/BilemoAPI.git`
puis dans le terminal ce positionner sur le chemin qui correspond au dossier que vous venez de créer
- activer l'extension sodium ( décommenter la ligne de l'extension dans le fichier php.ini)
- lancer la commande "composer install"
- créer la base de données, avec les commandes : 
 `php bin\console do:da:cr --env=dev`
- lancer les fixtures avec la commande : 
`php bin/console do:fi:lo`
- creer un dossier jwt dans le dossier config 
- puis entrer cette commande : 
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa
_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem
 -pubout

choisir une passphrase, et dans le .env changer la passphrase pour quelle corresponde à ce que vous avez choisis : JWT_PASSPHRASE=test

Une fois ces étapes réaliser l'API est prête à être testé. 

lancer la commande symfony server:start 

Pour voir les différentes routes possible vous pouvez allez sur : http://127.0.0.1:8000/api/doc dans votre navigateur

pour tester les routes vous pouvez le faire directement sur la page du nelmio ou bien avec postman
