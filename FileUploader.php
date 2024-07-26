<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Class FileUploader
 * Service de gestion de l'upload de fichiers.
 * @package App\Service
 */
class FileUploader
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    /**
     * FileUploader constructor.
     * @param string $targetDirectory Le répertoire cible pour les fichiers téléchargés.
     * @param SluggerInterface $slugger Service de gestion des slugs.
     */
    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    /**
     * Télécharge un fichier.
     * @param string $inputFile Le nom du champ de formulaire contenant le fichier.
     * @param object $form L'objet formulaire contenant les données du fichier.
     * @return string|null Le chemin du fichier téléchargé ou null si aucun fichier n'a été téléchargé.
     * @throws \Exception En cas d'erreur lors du déplacement du fichier.
     */
    public function upload(string $inputFile, object $form)
    {
        $file = $form->get($inputFile)->getData();

        if ($file instanceof UploadedFile) {

            // Valide le type MIME du fichier pour s'assurer qu'il est supporté
            $this->validateMimeType($file);

            // Vérifie si le fichier existe déjà en comparant les hachages
            $existingFile = $this->getFileByHash($file);
            if ($existingFile) {
                return 'uploads/' . $existingFile;
            }

            // Génère un nom unique pour le fichier
            $fileName = $this->getName($file);

            try {
                // Déplace le fichier vers le répertoire cible
                $file->move($this->getTargetDirectory(), $fileName);
            } catch (FileException $e) {
                throw new \Exception('Erreur lors du déplacement du fichier : ' . $e->getMessage());
            }

            // Retourne le chemin du fichier téléchargé pour le setter
            return 'uploads/' . $fileName;
        }

        return null;
    }

    /**
     * Valide le type MIME d'un fichier.
     * @param UploadedFile $file Le fichier à valider.
     * @throws FileException Si le type de fichier n'est pas supporté.
     */
    private function validateMimeType(UploadedFile $file)
    {
        $acceptMimeTypes = ['jpeg', 'png', 'jpg'];
        $mimeType = $file->guessExtension();

        if (!in_array($mimeType, $acceptMimeTypes)) {
            throw new FileException('Type de fichier non supporté. Veuillez télécharger une image au format jpeg, png ou jpg.');
        }
    }

    /**
     * Retourne le répertoire cible.
     * @return string Le répertoire cible.
     */
    private function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    /**
     * Cherche un fichier par son hash.
     * @param string $fileHash Le hash du fichier.
     * @return string|null Le nom du fichier existant ou null si aucun fichier n'a été trouvé.
     */
    private function findFileByHash(string $fileHash)
    {
        $files = scandir($this->getTargetDirectory());

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (md5_file($this->getTargetDirectory() . '/' . $file) === $fileHash) {
                    return $file;
                }
            }
        }

        return null;
    }

    /**
     * Retourne un fichier par son hash.
     * @param UploadedFile $file Le fichier à vérifier.
     * @return mixed|null Le fichier existant ou null si aucun fichier n'a été trouvé.
     */
    public function getFileByHash(UploadedFile $file): mixed
    {
        $fileHash = md5_file($file->getPathname());
        $existingFile = $this->findFileByHash($fileHash);
        return $existingFile;
    }

    /**
     * Génère un nom sûr pour un fichier.
     * @param UploadedFile $file Le fichier pour lequel générer un nom.
     * @return string Le nom généré.
     */
    public function getName(UploadedFile $file): string
    {
        // Récupère nom du fichier sans l'extension
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        // Convertit le nom de fichier original en un format sûr (suppression des accents, des espaces, etc.)
        $safeFilename = $this->slugger->slug($originalFilename);

        // Génère un nom unique pour le fichier en ajoutant un identifiant unique
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        return $fileName;
    }
}
