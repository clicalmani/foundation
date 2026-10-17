<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Http\Request;

use Clicalmani\Core\Http\Request\UploadedFile;
use Clicalmani\Core\Tests\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use ReflectionProperty;

/**
 * Tests de Clicalmani\Core\Http\Request\UploadedFile.
 *
 * La classe lit tout dans $_FILES[$name] via getFile() : la superglobale doit
 * donc être peuplée AVANT l'instanciation (le constructeur appelle
 * mime_content_type($this->getFile()->tmp_name), d'où un warning + une
 * TypeError sur une clé absente). is_uploaded_file() renvoie toujours false en
 * CLI : c'est la branche « non SAPI » qui s'exerce ici.
 */
final class UploadedFileTest extends TestCase
{
    private ?string $workDir = null;

    /* -----------------------------------------------------------------
     |  Outillage
     | -----------------------------------------------------------------
     */

    private function dir(): string
    {
        return $this->workDir ??= $this->tempDir('uploaded_file_');
    }

    /** Écrit un fichier temporaire réel dans le répertoire du test. */
    private function tempFile(string $filename, string $content = 'contenu du fichier'): string
    {
        $path = $this->dir() . DIRECTORY_SEPARATOR . $filename;

        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Peuple $_FILES[$key] en fichier simple puis instancie l'UploadedFile.
     *
     * @param array<string, mixed> $overrides Clés de l'entrée $_FILES à forcer.
     */
    private function upload(
        string $key,
        string $filename = 'rapport.pdf',
        string $content = 'contenu du fichier',
        array $overrides = []
    ): UploadedFile {
        $_FILES[$key] = array_merge([
            'name'      => $filename,
            'full_path' => $filename,
            'type'      => 'application/octet-stream',
            'tmp_name'  => $this->tempFile($filename, $content),
            'error'     => UPLOAD_ERR_OK,
            'size'      => strlen($content),
        ], $overrides);

        return new UploadedFile($key);
    }

    /**
     * Bascule une entrée $_FILES en multi-fichiers.
     *
     * Le constructeur ne sait pas lire ce format (mime_content_type() reçoit un
     * tableau) : l'état multiple est donc installé après construction, getFile()
     * relisant la superglobale à chaque appel.
     *
     * @param array<int, array{name: string, path: string, type?: string, error?: int, size?: int}> $files
     */
    private function switchToMultiple(string $key, array $files): void
    {
        $entry = [
            'name' => [], 'full_path' => [], 'type' => [],
            'tmp_name' => [], 'error' => [], 'size' => [],
        ];

        foreach ($files as $file) {
            $entry['name'][]      = $file['name'];
            $entry['full_path'][] = $file['name'];
            $entry['type'][]      = $file['type'] ?? 'application/octet-stream';
            $entry['tmp_name'][]  = $file['path'];
            $entry['error'][]     = $file['error'] ?? UPLOAD_ERR_OK;
            $entry['size'][]      = $file['size'] ?? 0;
        }

        $_FILES[$key] = $entry;
    }

    /**
     * Force l'état interne « déplacé ». moveTo() n'aboutit jamais (voir les
     * tests FIXME sur storage()->mkdir()), c'est le seul moyen d'atteindre les
     * gardes « already moved » imposées par PSR-7.
     */
    private function markAsMoved(UploadedFile $file): void
    {
        (new ReflectionProperty(UploadedFile::class, 'moved'))->setValue($file, true);
    }

    /* -----------------------------------------------------------------
     |  Lecture de la superglobale
     | -----------------------------------------------------------------
     */

    public function test_la_classe_implemente_l_interface_psr7(): void
    {
        $file = $this->upload('avatar');

        $this->assertInstanceOf(UploadedFileInterface::class, $file);
    }

    public function test_get_file_expose_l_entree_de_la_superglobale(): void
    {
        $this->upload('avatar', 'photo.jpg');

        $file = (new UploadedFile('avatar'))->getFile();

        $this->assertInstanceOf(\stdClass::class, $file);
        $this->assertSame('photo.jpg', $file->name);
        $this->assertSame('photo.jpg', $file->full_path);
        $this->assertSame(UPLOAD_ERR_OK, $file->error);
    }

    public function test_les_noms_du_fichier_sont_lus_dans_files(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        $this->assertSame('photo.jpg', $file->getName());
        $this->assertSame('photo.jpg', $file->getClientOriginalName());
        $this->assertSame('photo.jpg', $file->getClientFilename());
    }

    public function test_get_type_detecte_le_mime_cote_serveur(): void
    {
        // Le type déclaré par le client est volontairement fantaisiste.
        $file = $this->upload('avatar', 'photo.jpg', 'bidon', ['type' => 'text/csv']);

        $this->assertSame(mime_content_type($file->getFile()->tmp_name), $file->getType());
        $this->assertSame('text/csv', $file->getClientMediaType());
    }

    public function test_get_client_media_type_renvoie_le_type_declare_par_le_client(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'x', ['type' => 'image/jpeg']);

        $this->assertSame('image/jpeg', $file->getClientMediaType());
    }

    public function test_taille_et_code_erreur_viennent_de_la_superglobale(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'abcd');

        $this->assertSame(4, $file->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
    }

    public function test_les_arguments_du_constructeur_sont_ignores_au_profit_de_files(): void
    {
        $this->upload('avatar', 'photo.jpg', 'abcd');

        // type/size/error/sapi passés au constructeur sont écrasés par $_FILES.
        $file = new UploadedFile('avatar', 'image/png', 999, UPLOAD_ERR_NO_FILE, true);

        $this->assertSame(4, $file->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertNotSame('image/png', $file->getType());
        // is_uploaded_file() est faux en CLI : la branche SAPI n'est jamais prise.
        $this->assertFalse((new ReflectionProperty(UploadedFile::class, 'sapi'))->getValue($file));
    }

    /* -----------------------------------------------------------------
     |  Validité & codes d'erreur
     | -----------------------------------------------------------------
     */

    public function test_is_valid_est_vrai_quand_le_code_erreur_vaut_zero(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'x', ['error' => UPLOAD_ERR_OK]);

        $this->assertTrue($file->isValid());
    }

    public function test_is_valid_est_faux_des_qu_un_code_erreur_est_present(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'x', ['error' => UPLOAD_ERR_INI_SIZE]);

        $this->assertFalse($file->isValid());
        $this->assertSame(UPLOAD_ERR_INI_SIZE, $file->getError());
    }

    public function test_is_valid_accepte_un_code_erreur_numerique_sous_forme_de_chaine(): void
    {
        // Deux clés distinctes : getFile() relit $_FILES[$name] en direct, donc
        // deux uploads sur la même clé partageraient la dernière entrée écrite.
        $ok   = $this->upload('avatar_ok', 'photo.jpg', 'x', ['error' => '0']);
        $fail = $this->upload('avatar_ko', 'photo.jpg', 'x', ['error' => '2']);

        $this->assertTrue($ok->isValid());
        $this->assertSame(0, $ok->getError());
        $this->assertFalse($fail->isValid());
        $this->assertSame(2, $fail->getError());
    }

    public function test_get_error_renvoie_le_premier_code_en_echec_en_multi_fichiers(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt', 'path' => $path, 'error' => UPLOAD_ERR_OK],
            ['name' => 'b.txt', 'path' => $path, 'error' => UPLOAD_ERR_INI_SIZE],
            ['name' => 'c.txt', 'path' => $path, 'error' => UPLOAD_ERR_PARTIAL],
        ]);

        // L'interface PSR-7 impose un int : c'est le premier code non nul qui
        // remonte, pas le tableau complet.
        $this->assertSame(UPLOAD_ERR_INI_SIZE, $file->getError());
        $this->assertFalse($file->isValid());
    }

    public function test_get_error_vaut_ok_en_multi_fichiers_quand_tout_est_passe(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt', 'path' => $path],
            ['name' => 'b.txt', 'path' => $path],
        ]);

        $this->assertSame(UPLOAD_ERR_OK, $file->getError());
        $this->assertTrue($file->isValid());
    }

    /* -----------------------------------------------------------------
     |  Extensions
     | -----------------------------------------------------------------
     */

    public function test_get_extension_lit_l_extension_du_fichier_temporaire(): void
    {
        // Le nom temporaire porte une extension différente du nom client.
        $file = $this->upload('avatar', 'rapport.pdf', 'x');

        $_FILES['avatar']['tmp_name'] = $this->tempFile('phpABCD.tmp', 'x');

        $this->assertSame('tmp', $file->getExtension());
    }

    public function test_get_extension_vaut_une_chaine_vide_sans_point_dans_le_nom_temporaire(): void
    {
        $file = $this->upload('avatar', 'rapport.pdf', 'x');

        $_FILES['avatar']['tmp_name'] = $this->tempFile('phpABCD', 'x');

        $this->assertSame('', $file->getExtension());
    }

    public function test_get_client_original_extension_lit_le_nom_client(): void
    {
        $file = $this->upload('avatar', 'photo.jpeg');

        $this->assertSame('jpeg', $file->getClientOriginalExtension());
    }

    public function test_get_client_original_extension_renvoie_un_tableau_en_multi_fichiers(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt',  'path' => $path],
            ['name' => 'b.png',  'path' => $path],
            ['name' => 'c.jpeg', 'path' => $path],
        ]);

        $this->assertSame(['txt', 'png', 'jpeg'], $file->getClientOriginalExtension());
    }

    public function test_get_client_original_extension_rogne_le_premier_caractere_d_un_nom_sans_point(): void
    {
        $file = $this->upload('avatar', 'LICENSE');

        // FIXME: strrpos() renvoie false, donc substr($name, false + 1) ampute le
        // premier caractère au lieu de renvoyer une extension vide.
        $this->assertSame('ICENSE', $file->getClientOriginalExtension());
    }

    /* -----------------------------------------------------------------
     |  Multi-fichiers
     | -----------------------------------------------------------------
     */

    public function test_is_multiple_et_count_sur_un_fichier_simple(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        $this->assertFalse($file->isMultiple());
        $this->assertSame(1, $file->count());
    }

    public function test_is_multiple_et_count_en_multi_fichiers(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt', 'path' => $path],
            ['name' => 'b.txt', 'path' => $path],
            ['name' => 'c.txt', 'path' => $path],
        ]);

        $this->assertTrue($file->isMultiple());
        $this->assertSame(3, $file->count());
        $this->assertSame(['a.txt', 'b.txt', 'c.txt'], $file->getName());
    }

    public function test_les_accesseurs_psr7_renvoient_la_premiere_entree_en_multi_fichiers(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt', 'path' => $path, 'type' => 'text/plain'],
            ['name' => 'b.png', 'path' => $path, 'type' => 'image/png'],
        ]);

        // ?string imposé par l'interface : seule la première entrée remonte.
        $this->assertSame('a.txt', $file->getClientFilename());
        $this->assertSame('text/plain', $file->getClientMediaType());
    }

    public function test_le_constructeur_refuse_une_entree_multi_fichiers(): void
    {
        $path = $this->tempFile('a.txt');

        $_FILES['gallery'] = [
            'name'      => ['a.txt', 'b.txt'],
            'full_path' => ['a.txt', 'b.txt'],
            'type'      => ['text/plain', 'text/plain'],
            'tmp_name'  => [$path, $path],
            'error'     => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size'      => [1, 1],
        ];

        // FIXME: le constructeur passe tmp_name (un tableau ici) à
        // mime_content_type() : un upload multiple ne peut pas être instancié.
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('mime_content_type(): Argument #1 ($filename) must be of type resource|string, array given');

        new UploadedFile('gallery');
    }

    /* -----------------------------------------------------------------
     |  addFile()
     | -----------------------------------------------------------------
     */

    public function test_add_file_cree_l_entree_quand_la_cle_est_absente(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');
        $path = $this->tempFile('piece-jointe.txt', 'abc');

        unset($_FILES['avatar']);

        $file->addFile('piece-jointe.txt', $path, 3, 'text/plain');

        $this->assertSame([
            'name'      => 'piece-jointe.txt',
            'full_path' => 'piece-jointe.txt',
            'type'      => 'text/plain',
            'tmp_name'  => $path,
            'error'     => UPLOAD_ERR_OK,
            'size'      => 3,
        ], array_diff_key((array) $file->getFile(), ['time' => true]));
    }

    public function test_add_file_marque_une_taille_nulle_comme_erreur(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        unset($_FILES['avatar']);

        $file->addFile('vide.bin', $this->tempFile('vide.bin', ''), 0, 'application/octet-stream');

        $this->assertSame(1, $file->getFile()->error);
        $this->assertFalse($file->isValid());
    }

    public function test_add_file_ajoute_une_entree_en_multi_fichiers(): void
    {
        $file = $this->upload('gallery', 'a.txt', 'aaa');
        $first = $file->getFile()->tmp_name;
        $second = $this->tempFile('b.png', 'bb');

        $this->switchToMultiple('gallery', [['name' => 'a.txt', 'path' => $first, 'size' => 3]]);

        $file->addFile('b.png', $second, 2, 'image/png');

        $this->assertSame(2, $file->count());
        $this->assertSame(['a.txt', 'b.png'], $file->getName());
        $this->assertSame(['txt', 'png'], $file->getClientOriginalExtension());
    }

    public function test_add_file_ne_fait_rien_si_la_cle_existe_deja_en_fichier_simple(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');
        $path = $this->tempFile('autre.txt', 'abc');

        $file->addFile('autre.txt', $path, 3, 'text/plain');

        // FIXME: la seconde branche de addFile() exige isMultiple() ; sur une
        // entrée simple déjà présente, l'appel est silencieusement ignoré.
        $this->assertSame('photo.jpg', $file->getName());
        $this->assertFalse($file->isMultiple());
    }

    /* -----------------------------------------------------------------
     |  Flux
     | -----------------------------------------------------------------
     */

    public function test_get_stream_expose_le_contenu_du_fichier_temporaire(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'contenu du flux');

        $stream = $file->getStream();

        $this->assertInstanceOf(StreamInterface::class, $stream);
        $this->assertSame(strlen('contenu du flux'), $stream->getSize());
        $this->assertSame('contenu du flux', $stream->getContents());
    }

    public function test_get_stream_met_le_flux_en_cache(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        $this->assertSame($file->getStream(), $file->getStream());
    }

    public function test_get_stream_refuse_un_fichier_deja_deplace(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        $this->markAsMoved($file);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve stream after it has already been moved');

        $file->getStream();
    }

    /* -----------------------------------------------------------------
     |  Déplacements — comportement actuel (chemins cassés)
     | -----------------------------------------------------------------
     */

    public function test_move_to_refuse_un_fichier_deja_deplace(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        $this->markAsMoved($file);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot move file; already moved!');

        $file->moveTo($this->dir() . DIRECTORY_SEPARATOR . 'dest.jpg');
    }

    public function test_move_to_refuse_un_fichier_en_erreur(): void
    {
        $file = $this->upload('avatar', 'photo.jpg', 'x', ['error' => UPLOAD_ERR_NO_FILE]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot retrieve stream due to upload error');

        $file->moveTo($this->dir() . DIRECTORY_SEPARATOR . 'dest.jpg');
    }

    public function test_move_to_echoue_sur_le_storage(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        // FIXME: storage() appelle StorageManager::drive(null) qui n'est pas
        // nullable ; et même avec un disque, mkdir() n'existe ni sur
        // StorageManager ni sur League\Flysystem\Filesystem (BadMethodCallException).
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches('/StorageManager::drive\(\): Argument #1 \(\$name\) must be of type string, null given/');

        $file->moveTo($this->dir() . DIRECTORY_SEPARATOR . 'dest.jpg');
    }

    public function test_move_echoue_sur_le_storage(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        // FIXME: moveFile() appelle storage()->mkdir(), injoignable (drive(null)).
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches('/StorageManager::drive\(\): Argument #1 \(\$name\) must be of type string, null given/');

        $file->move($this->dir(), 'dest.jpg');
    }

    public function test_move_sans_repertoire_echoue_sur_le_storage(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        // FIXME: même cause — storage()->mkdir(storage_path('/public')).
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches('/StorageManager::drive\(\): Argument #1 \(\$name\) must be of type string, null given/');

        $file->move();
    }

    public function test_store_delegue_a_move_et_echoue_de_la_meme_faccon(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');

        // FIXME: store() -> move() -> storage()->mkdir(), injoignable (drive(null)).
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches('/StorageManager::drive\(\): Argument #1 \(\$name\) must be of type string, null given/');

        $file->store('dest.jpg');
    }

    public function test_move_en_multi_fichiers_echoue_aussi_sur_le_storage(): void
    {
        $file = $this->upload('gallery', 'a.txt');
        $path = $file->getFile()->tmp_name;

        $this->switchToMultiple('gallery', [
            ['name' => 'a.txt', 'path' => $path],
            ['name' => 'b.txt', 'path' => $path],
        ]);

        // FIXME: storage()->mkdir() injoignable (drive(null)) dans moveFile().
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessageMatches('/StorageManager::drive\(\): Argument #1 \(\$name\) must be of type string, null given/');

        $file->move($this->dir(), ['a.txt', 'b.txt']);
    }

    public function test_le_fichier_source_n_est_pas_deplace_quand_le_deplacement_echoue(): void
    {
        $file = $this->upload('avatar', 'photo.jpg');
        $source = $file->getFile()->tmp_name;

        try {
            $file->moveTo($this->dir() . DIRECTORY_SEPARATOR . 'dest.jpg');
            $this->fail('moveTo() aurait dû lever une TypeError.');
        } catch (\TypeError) {
            // Comportement attendu (voir test_move_to_echoue_sur_le_storage).
        }

        $this->assertFileExists($source);
        $this->assertFileDoesNotExist($this->dir() . DIRECTORY_SEPARATOR . 'dest.jpg');
    }
}
