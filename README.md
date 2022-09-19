# Guide Symfony pour créer un projet de base

## étapes:

- ### Installation de Symfony et Maker Bundle
  
  ```bash
  # Telechargement de l'installateur
  wget https://get.symfony.com/cli/installer -O - | bash 
  ```

# Bouger l'exécutable dans le dossier global d'exécutables

```bash
mv /home/utilisateur/.symfony/bin/symfony /usr/local/bin/symfony
```

# création d'un projet Symfony (se placer dans le répertoire ou on veut le projet)

```bash
symfony new MonProjet
```

# Installation du composant 'Maker Bundle' afin d'avoir accès a des bibliothèques symfony

```bash
composer require symfony/maker-bundle --dev
```

explication: composer est le gestionnaire et installateur de bibliothèques, `require` est l'action a exécuter, `symfony/maker-bundle` est la bibliotheque a installer, l'option `--dev` précise qu'il faut sauvegarder la bibliotheque en tant que dépendance de développement (elle ne sera pas nécessaire a installer en environnement de Production)

2. # Création et configuration de la BDD
   
   - Installation de Doctrine ORM
   
   ```bash
   composer require symfony/orm-pack
   
   # l'installation de l'ORM va nous generer quelques fichiers 
   # et va notamment ecrire une ligne de config dans notre fichier .env,
   # elle ressemble a:
   
   DATABASE_URL="postgresql://postgres:@127.0.0.1:5432/db_name?serverVersion=13.3&charset=utf8"
   ```
   
   
   

3. # Création de la BDD

```bash
symfony console doctrine:database:create

# cette commande va indiquer a Doctrine ORM de creer une base de donnees avec les informations renseignees dans la ligne de de config ci dessus, en gros:
"{ENGIN_BDD}://{UTILISATEUR}:{MOT_DE_PASSE}@{ADDRESSE}:{PORT}/{NOM_BDD}?serverVersion=13.3&charset=utf8"

# dans notre cas de figure, on creera la BDD avec PostgreSql, utilisateur postgres, mot de passe vide, dans localhost, port par defaut 5432 d'ou:
"postgresql://postgres:@127.0.0.1:5432/db_name?serverVersion=13.3&charset=utf8"
```

Si la commande précédente est exécutée avec succès, on peut passer a la prochaine étape. 

3. # Création des entités
   
   Grace a l'interface de ligne de commande proposée par `php maker-bundle`

```bash
symfony console make:entity

# se laisser guider par le programme, saisir d'abord le nom souhaite pour la table, les noms des colonnes, ses types (STR, VARCHAR, INT...) a savoir qu'une colonne ID AUTO_INCREMENT est cree toute seule, pas besoin de la saisir manuellement
```

- Installer le bundle `security` afin d'avoir une entité 'utilisateur' a partir de laquelle on construit la notre

```bash
composer require security

symfony console make:user

# Note: on peut bien sur creer notre propre modele d'utilisateur a partir de 0, cependant heriter depuis une entite prefabriquee a ses avantages, notamment:
# 1.) fonctionnalite de hachage du mot de passe inclue par defaut, c.a.d que le MDP stocke en BDD sera une chaine de caracteres "hachee" donc illisible sans la bonne cle de chiffrage (seul a travers l'ORM aura t-on acces a la lecture de celui ci)
# 2.) Identifiants uniques par defaut (au choix: email, nom d'utilisateur). cela nous permet d'avoir des contraintes d'unicite sur une colonne sans avoir a le faire nous meme
# 3.) systeme de roles predefini et inclu par defaut (si jamais on souhaite affecter des roles avec des privileges superieurs a un utilisateur (ex. admin) on aura la possibilite sans ajouter de la logique supplementaire)
```

Faire ensuite les autres entités

4. # Mettre a jour les entités
   
   ## Ajouter les relations
   
   ### (utilisateur->post par exemple)

```bash
symfony console make:entity

# on utilise la meme commande pour mettre a jour une entite, lorsque l'interface nous demandera quelle entite on souhaite creer, si on saisit une entite existante, il la reconnaitra automatiquement et il vous proposera de rajouter des champs. pour ajouter un champ de type relation il suffit de le preciser ainsi:

symfony console make:entity

 Class name of the entity to create or update (e.g. OrangeGnome):
 > User

Your entity already exists! So lets add some new fields!

 New property name (press <return> to stop adding fields):
 > posts

 Field type (enter ? to see all types) [string]:
 > relation

 Relation type? [ManyToOne, OneToMany, ManyToMany, OneToOne]:
 > OneToMany

 # l'outil nous proposera aussi par defaut la possibilite d'ajouter 
 # la relation inverse. ici par exemple on obtiendrait post->user. 
 # Cela est pratique mais pas toujours souhaitable
```

Une fois ces étapes réalisées avec succès, deux fichiers sont générées et mis a jour dans `src/Entity/`. Ces fichiers correspondent aux déclaration de Classes PHP regroupant les propriétés des entités qu'on a déclare lors de la saisie en ligne de commande. 

---

## Note sur le fonctionnement et la structure d'une entité Doctrine

Si on va dans `src/Entity/User.php` par exemple, on retrouve les propriétés qui seront converties par l'ORM en colonnes BDD. (lignes précédées d'une annotation `@ORM\Column`)

```php
    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;
```

explication:

- `private`: mot clé propre a la programmation orientée objet. il précède une propriété qu'on souhaite rendre inaccessible en dehors du contexte de l'objet, on pourra la solliciter seulement a travers son `getter`
  
    en effet, plus en bas du fichier on trouve:
  
  ```php
  public function getEmail(): ?string
  {
      return $this->email;
  }
  ```

- `$email` le nom de la propriété

- `@` une annotation c'est de la méta donnée d'une propriété ou d'une méthode, ça peut être utile pour le développeur mais peut également faire office d'instruction particulière pour l'interpréteur PHP. A ne pas confondre avec un **commentaire de documentation**, la différence étant que ce dernier n'a pas vocation à signaler de comportement particulier.
  
  - dans notre cas de figure
    
    ```php
    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    
    // Dans ce cas, doctrine va lire ces informations @ORM/Column et fera de sorte que notre propriété de la classe PHP soit bien "traduite" en colonne de la table au niveau SQL
    ```

## Fin de la parenthèse sur les entités

  ---

6. # Créer et exécuter les migrations

Une fois que nos entités ont été crées et qu'on est satisfaits avec, on peut procéder a la génération des migrations. Une migration est une série d'instructions a transmettre a l'engin de la BDD afin qu'il convertisse nos Classes PHP en commandes SQL pour définir le 'schéma' soit les tables, les colonnes, les relations etc.

Pour cela on génère d'abord les fichiers migration:

```bash
symfony console make:migration

# ceci generera des fichiers dans ./migrations
```

Ensuite, si on a pas eu d'erreur, on les applique:

```bash
symfony console doctrine:migration:migrate
```

Si ces deux commandes ont abouti sans erreurs, notre BDD a été créée et schématisée selon nos entités / classes PHP. La BDD est donc prête a être sollicitée.

7. # Convertir nos entités en ressources API

## Introduction a API Platform

API Platform est un bundle Symfony qui nous permet de sérialiser nos classes PHP en **Ressources d'API REST-Conforme** afin d'être servis via HTTP. 

En ajoutant quelques lignes de code a nos entités, API Platform va générer toute la logique nécessaire pour cela.
(contrôleurs, router, endpoints et les méthodes acceptées, etc).

pour cela, il suffit de se rendre dans nos classes d'entités (`src/Entity/NomDeLentite.php`)
et de les annoter avec la ligne `@ApiResource()`:

```php
use ApiPlatform\Core\Annotation\ApiResource;
// Ne pas oublier de faire l'import des annotations

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=MonEntiteRepository::class)
 */
class MonEntite
{
    ...
}
```

# Authentification par JWT

## Avant propos: Authentification avec un mot de passe haché

Etant donne que notre classe User admet une propriété `password` qui sera chiffrée en BDD, il nous faut bien un moyen de pouvoir la mettre en place pour la première fois, une initialisation en quelque sorte. Pour cela, on ajoutera une colonne `plainPassword`

```bash
$ symfony console make:entity

>User
>plainPassword
>string (180)
>nullable? [yes] # lors de l'initialisation, (creation de compte) on viendra affecter la valeur null en BDD a ce plainPassword et on utilisera dorenavant le password chiffre

$ symfony console make:migration
$ symfony console doctrine:migration:migrate
```

Maintenant qu'on a un `plainPassword` qui va nous servir d'intermédiaire, on doit créer un `DataPersister` pour l'entité User.

Un `DataPersister` est une classe qui nous permet de faire un traitement sur la donnée avant de l'écrire définitivement en BDD.

Dans notre cas de figure, la logique est la suivante:

- On reçoit un MDP 'plain' via requête `HTTP` `POST`
- On le récupère et on affecte sa valeur au MDP définitif
- On encode le MDP définitif
- On supprime le MDP provisoire reçu en plein text
- On **persiste** en BDD

Un exemple typique de classe `UserDataPersister` ci dessous

```php
namespace App\DataPersister;
use ApiPlatform\Core\DataPersister\DataPersisterInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserDataPersister implements DataPersisterInterface
{
    /**
     * @var UserPasswordHasherInterface
     */
    private $encoder;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * UserPersister constructor.
     * @param UserPasswordHasherInterface $encoder
     * @param EntityManagerInterface $manager
     */
    public function __construct(
        UserPasswordHasherInterface $encoder,
        EntityManagerInterface $manager
    )
    {
        $this->encoder = $encoder;
        $this->manager = $manager;
    }

    /**
     *********************************************
     * Détermine si oui ou non notre objet $data
     * est une instance de App/Entity/User.
     *********************************************
     * @param $data
     * @return bool
     */
    public function supports($data): bool
    {
        return $data instanceof User;
    }

    /**
     *****************************************************************
     * Méthode venant encoder le  plain password de l'utilisateur
     * avant de pousser ses informations en BDD.
     *****************************************************************
     * @param $data
     * @return object|void
     */
    public function persist($data)
    {
        if($data->getPlainPassword()){

            $data->setPassword(
                $this->encoder->hashPassword(
                $data, $data->getPlainPassword()
            ));
            $data->eraseCredentials();

        }

        $this->manager->persist($data);
        $this->manager->flush();
    }

    /**
     ***********************************************
     * Cette méthode précise quoi faire au moment
     * de la suppression de cet objet $data.
     ***********************************************
     * @param $data
     */
    public function remove($data)
    {
        $this->manager->remove($data);
        $this->manager->flush();
    }
}
```

Le fonctionnement dans le détail d'un `DataPersister` est en dehors du cadre de ce projet, il faut simplement garder en tête que c'est un moyen utile d'ajouter un traitement spéifique avant l'écriture en BDD. 

Un autre cas dans lequel un Persister serait utile, c'est pour changer le nom d'un fichier d'image soumis par l'utilisateur, en quelque chose d'unique et de plus structure. Par ex:
On reçoit en entrée un nom de fichier 
`photo1.jpeg`
et on le transforme en 
`media/users/1/books/qw5ehsa52313j2.jpg`

Maintenant qu'on a toute la logique nécessaire, on passe a la génération et la configuration de `tokens` JWT

## Installation

```bash
$ composer require jwt
```

## Génération de paire clé publique - clé privée

```bash
$ symfony console lexik:jwt:generate-keypair
```

Explication en gros:
La clé publique servira a décoder une `token` , tandis que la clé privée servira a générer des nouvelles `token` ainsi qu'à valider la signature de la clé publique. Ceci étant de l'information critique de l'application, il faut **Veiller a ne pas les versionner** (dans .gitignore, cibler le fichier des clés fini en `.pem` contenues dans `/config/jwt`)

### Paramétrer les fonctionnalités de login, déclarer ses pare-feus, et sécuriser les routes dans le fichier `security.yaml`

exemple de fichier `security.yaml`

```yaml
security:
    enable_authenticator_manager: true
    password_hashers:
        App\Entity\User:
            algorithm: auto

    providers:
        # Dans notre projet, le fournisseur de la logique
        # utilisateur sera notre entite User 
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email


    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        login:
            # 'patron' du chemin a controler
            pattern: ^/api/login

            # stateless: chaque requete vers login,
            # est independante de la derniere
            stateless: true                

            # quel fournisseur de logique utlisateur
            provider: app_user_provider

            # etant donne que notre client distant 
            # communique en JSON uniquement, on declare
            # le type de login a json_login (autre possibilite
            # ce serait du form_login si on avait des templates
            # genere cote serveur)
            json_login:

                # ici vu que la seule maniere de s'authentifier
                # est via JSON, alors le chemin est le meme
                check_path: /api/login

                # par defaut, le bundle lexik:jwt verifiera dans
                # le corps de la requete 
                # une cle 'username' et une cle 'password', 
                # or, nous on souhaite qu'au lieu de verifier le pseudo, 
                # ce soit l'adresse mail qui serve d'identifiant unique
                username_path: email
                password_path: password

                # gestionnaires de succes et d'echec 
                # (il convient de laisser les valeurs par defaut)
                success_handler: lexik_jwt_authentication.handler.authentication_success
                failure_handler: lexik_jwt_authentication.handler.authentication_failure

        # ce parefeu indique a Lexik:jwt que toutes les routes
        # commencant par /api pourront aussi etre 'controlees'
        api:
            pattern: ^/api
            stateless: true
            provider: app_user_provider
            guard:
                authenticators:
                    - lexik_jwt_authentication.jwt_token_authenticator

    # Controle d'acces personalise vers les routes qu'on souhaite proteger. 
    # Il convient de commencer par les 'exceptions' en premier
    # et ensuite les cas generaux
    access_control:

        # la route login sera evidement accesible a tout le monde
        # afin qu'on puisse s'identifier
        - { path: ^/api/login, roles: IS_AUTHENTICATED_ANONYMOUSLY}

        # la route /api/users sera aussi accessible en mode anonyme 
        # afin que l'on puisse creer un compte sans etre authentifie, 
        # ce qui est logique. 
        # D'ou l'autorisation vers la route en methode POST uniquement 
        # (on souhaite pas qu'on puisse recuperer
        # des infos utilisateurs de maniere anonyme)
        - { path: ^/api/users, method: [POST], roles: IS_AUTHENTICATED_ANONYMOUSLY }

        # pour le reste des routes commencant par /api, 
        # il faudra etre authentifie! 
        # c.a.d soit il y a une token valide en en-tete de requete
        # soit une reponse 401 (non autorise) sera retournee
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY } 
```

Il faut aussi déclarer la route vers login dans le fichier `routes.yaml`

```yaml
# chemin de connexion
api_login:
    path: /api/login

# chemin de deconnexion
api_logout:
    path: /api/logout
```

Tester par la suite la génération de token JWT soit depuis les docs. dans /api, soit depuis postman ou un client distant, soit avec l'outil de ligne de commande `curl`, exemple:

```bash
$ curl -X POST -H \
    "Content-Type: application/json" \
    http://localhost:8000/api/login -d \
    '{"email":"saisir@email.valide","password":"mdp"}' 
```

Si tout s'est bien passe, la réponse devrait ressembler a ça:

```bash
{"token":"eyJ0eXAiOiJKV[...]ca38f7689b85730101"}
```

A ce stade on est prêts a servir des JWT via HTTP afin d'authentifier les utilisateurs et restreindre l'accès a des ressources si nécessaire. 

Afin de solliciter les ressources auxquels l'utilisateur porteur de ce token a accès, il suffit de mettre le token dans un en-tête ou `header` lors de chaque requête. 

par exemple, en JavaScript:

```js
const headers = {
    "Content-Type": "application/json",
    "Authorization": "Bearer eyJ0eXAiOiJKV..."
    // ...
}

const response = await fetch(`${monUrl}/api/books`, headers)
```

**Coté navigateur il faut veiller a garder ces tokens de manière sécurisée**, ne jamais le rendre lisibles via des print en console ou dans le document.

---

---

---

# Optimisations bonus

### Dans le dév, rien n'est jamais fini à 100%.

#### Quelques problématiques demeurent sans solution jusqu'à présent, notamment:

1. **Une JWT a une durée de validité** au delà de laquelle, elle ne permettra plus a l'utilisateur de récupérer les informations du serveur. Il faut donc qu'il s'authentifie a nouveau grâce a son email + mdp. Mais cela n'est pas du tout pratique pour l'utilisateur. La solution parait elle simple non? affecter une durée de vie illimitée! Cela est possible techniquement, et acceptable pour un POC. En revanche, pour une application de production, il faut plutôt s'abstenir. Une bonne solution consiste à renouveller la durée de vie du token via ce qu'on appelle un **refresh token**

2. **Lors de l'authentification distante, seul un JWT est retourné**, suite a cette opération on doit récupérer certaines infos utilisateur en faisant une nouvelle requête vers `/users` et filtrer par identifiant unique (email) afin d'obtenir son `id`, c'est seulement cette dernière qui nous permettra de solliciter d'autres ressources en rapport avec l'utilisateur. Il faut donc, authentifier l'utilisateur, aller chercher son id, et seulement là on est prêts a récupérer d'autres ressources. On peut surement raccourcir la chaîne de requêtes. On verra comment grâce aux écouteurs d'événement `Symfony` 

3. Lors des requêtes vers `/users`, nous devons à chaque fois décoder la token puis **solliciter la base de données** pour récupérer les informations demandées. Or, une  JWT contient déjà la plupart de ces informations, ce qui rend ce tour en BDD complètement inutile. On verra comment on peut s'en passer avec un fournisseur d'utilisateur JWT ou en anglais `user provider`

```bash
$ symfony console make:subscriber JWT
```




Par défaut, la réponse lors de l'authentification contient uniquement du JSON correspondant au JWT. Nous pouvons modifier cela en déclarant un service dans `config/services.yaml`

```yaml
# le nom du service
acme_api.event.authentication_success_listener:

        # Quelle classe va gerer notre logique (a retenir puisque
        # on va devoir creer ce repertoire et ce fichier PHP)
        class: App\EventListener\AuthenticationSuccessListener
        tags:
            - { 
                name: kernel.event_listener, 
                event: lexik_jwt_authentication.on_authentication_success, 
                method: onAuthenticationSuccessResponse 
              }
```

Inutile de se prendre la tête a déchiffrer chaque ligne de config, cependant nous pouvons nous donner une idée de ce a quoi correspond chacune. Notamment on voit dans les `tags` qu'il y a un nom, en l'occurrence il s'agit d'un `event_listener`, l'événement c'est `lexik_jwt_authentication.on_authentication_success`, et la méthode `onAuthenticationSuccessResponse`. 

Si on essaye de paraphraser pour mieux comprendre de quoi s'agit il:

- C'est un écouteur d'événement

- Il écoute le succès d'authentification `lexik_jwt`

- Il déclenche la méthode Réponse Lors d'une Authentification Avec Succès

- Il pointe vers le fichier `AuthenticationSuccessListener`, contenu dans le dossier `EventListener` qu'on va créer tout de suite

[... en cours de rédaction]
