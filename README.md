# FileUploader-Symfony

**FileUploader** est un service PHP conçu pour gérer le téléchargement de fichiers dans une application Symfony. Il permet de valider, déplacer, et nommer les fichiers téléchargés de manière sécurisée.

## Installation

1. Assurez-vous que votre projet utilise Composer et Symfony.
2. Ajoutez la classe `FileUploader` dans le répertoire `src/Service` de votre projet Symfony.

## Configuration

Assurez-vous de configurer le service dans votre fichier de configuration `services.yaml` :

```yaml
services:
    App\Service\FileUploader:
        arguments:
            $targetDirectory: '%kernel.project_dir%/assets/uploads'
```

## Utilisation

### Injection du Service

Injectez le service `FileUploader` dans votre méthode de contrôleur ou autre service où vous avez besoin de gérer des fichiers téléchargés :

```php
public function edit(Request $request, Book $book, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
{
    // ... votre code ...
    if ($form->isSubmitted() && $form->isValid()) {
        $fileName = $fileUploader->upload('poster', $form);
        if ($fileName) {
            $book->setPoster($fileName);
        }

        $entityManager->flush();
        // ... votre code ...
    }
    // ... votre code ...
}

```

## Fonctionnalités

### Méthodes Publiques

- **`upload(string $inputFile, object $form)`** : Télécharge un fichier depuis un champ de formulaire, valide son type MIME, génère un nom unique et déplace le fichier vers le répertoire cible.

### Méthodes Privées

- **`validateMimeType(UploadedFile $file)`** : Valide le type MIME d'un fichier pour s'assurer qu'il est supporté.
- **`getTargetDirectory()`** : Retourne le répertoire cible pour les fichiers téléchargés.
- **`findFileByHash(string $fileHash)`** : Cherche un fichier par son hash MD5.
- **`getFileByHash(UploadedFile $file)`** : Retourne un fichier existant basé sur son hash MD5.
- **`getName(UploadedFile $file)`** : Génère un nom sûr et unique pour un fichier téléchargé.

## Exemples

### Validation du Type MIME

La méthode `validateMimeType` permet de s'assurer que seuls les fichiers de types `jpeg`, `png` ou `jpg` sont acceptés :

```php
private function validateMimeType(UploadedFile $file)
{
    $acceptMimeTypes = ['jpeg', 'png', 'jpg'];
    $mimeType = $file->guessExtension();

    if (!in_array($mimeType, $acceptMimeTypes)) {
        throw new FileException('Type de fichier non supporté. Veuillez télécharger une image au format jpeg, png ou jpg.');
    }
}
```

### Génération d'un Nom de Fichier Unique

La méthode `getName` génère un nom de fichier sûr et unique en utilisant un slug et un identifiant unique :

```php
public function getName(UploadedFile $file): string
{
    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    $safeFilename = $this->slugger->slug($originalFilename);
    return $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
}
```

