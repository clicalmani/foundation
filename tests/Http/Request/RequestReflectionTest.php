<?php

declare(strict_types=1);

namespace Clicalmani\Core\Tests\Http\Request;

use Clicalmani\Core\Http\Request\RequestReflection;
use Clicalmani\Core\Tests\Stubs\RequestReflectionControllerStub;
use Clicalmani\Core\Tests\Stubs\StubModel;
use Clicalmani\Core\Tests\TestCase;
use ReflectionException;
use ReflectionParameter;

/**
 * Tests de Clicalmani\Core\Http\Request\RequestReflection, simple wrapper de
 * ReflectionMethod utilisé pour résoudre les paramètres d'une action de
 * contrôleur.
 */
final class RequestReflectionTest extends TestCase
{
    private function reflection(string $method): RequestReflection
    {
        return new RequestReflection(RequestReflectionControllerStub::class, $method);
    }

    /* -----------------------------------------------------------------
     |  getParameters()
     | -----------------------------------------------------------------
     */

    public function test_get_parameters_renvoie_les_reflection_parameter_de_l_action(): void
    {
        $parameters = $this->reflection('typed')->getParameters();

        $this->assertIsArray($parameters);
        $this->assertCount(2, $parameters);

        foreach ($parameters as $parameter) {
            $this->assertInstanceOf(ReflectionParameter::class, $parameter);
        }

        $this->assertSame(['name', 'age'], array_map(
            static fn(ReflectionParameter $parameter): string => $parameter->getName(),
            $parameters
        ));
    }

    public function test_get_parameters_renvoie_un_tableau_vide_sans_parametre(): void
    {
        $this->assertSame([], $this->reflection('noParameter')->getParameters());
    }

    public function test_get_parameters_construit_de_nouvelles_instances_a_chaque_appel(): void
    {
        $reflection = $this->reflection('typed');

        $this->assertNotSame($reflection->getParameters(), $reflection->getParameters());
        $this->assertNotSame(
            $reflection->getParameters()[0],
            $reflection->getParameters()[0]
        );
    }

    /* -----------------------------------------------------------------
     |  getParamsTypes()
     | -----------------------------------------------------------------
     */

    public function test_get_params_types_indexe_les_types_scalaires_par_nom_de_parametre(): void
    {
        $this->assertSame(
            ['name' => 'string', 'age' => 'int'],
            $this->reflection('typed')->getParamsTypes()
        );
    }

    public function test_get_params_types_vaut_null_pour_un_parametre_non_type(): void
    {
        $this->assertSame(['anything' => null], $this->reflection('untyped')->getParamsTypes());
    }

    public function test_get_params_types_renvoie_un_tableau_vide_sans_parametre(): void
    {
        $this->assertSame([], $this->reflection('noParameter')->getParamsTypes());
    }

    public function test_get_params_types_perd_la_nuance_nullable(): void
    {
        // ReflectionNamedType::getName() renvoie le nom nu : « ?string » -> « string ».
        $this->assertSame(['tag' => 'string'], $this->reflection('nullable')->getParamsTypes());
    }

    public function test_get_params_types_renvoie_le_nom_complet_d_une_classe(): void
    {
        $this->assertSame(
            ['model' => StubModel::class],
            $this->reflection('classTyped')->getParamsTypes()
        );
    }

    public function test_get_params_types_sur_un_parametre_variadique(): void
    {
        $this->assertSame(['items' => 'string'], $this->reflection('variadic')->getParamsTypes());
    }

    public function test_get_params_types_echoue_sur_un_type_union(): void
    {
        // FIXME: ReflectionUnionType n'a pas de getName() ; getParamsTypes()
        // devrait passer par __toString() ou exposer la liste des types.
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ReflectionUnionType::getName()');

        $this->reflection('union')->getParamsTypes();
    }

    /* -----------------------------------------------------------------
     |  getParamTypeAt()
     | -----------------------------------------------------------------
     */

    public function test_get_param_type_at_renvoie_le_type_du_premier_parametre(): void
    {
        // La méthode ne prend aucun argument : elle renvoie toujours le premier
        // type de la liste (array_shift), pas le type « à une position donnée ».
        $this->assertSame('string', $this->reflection('typed')->getParamTypeAt());
        $this->assertSame(StubModel::class, $this->reflection('classTyped')->getParamTypeAt());
    }

    public function test_get_param_type_at_vaut_null_sans_parametre(): void
    {
        $this->assertNull($this->reflection('noParameter')->getParamTypeAt());
    }

    public function test_get_param_type_at_vaut_null_si_le_premier_parametre_n_est_pas_type(): void
    {
        $this->assertNull($this->reflection('untyped')->getParamTypeAt());
    }

    public function test_get_param_type_at_echoue_sur_un_type_union(): void
    {
        // FIXME: même cause que getParamsTypes() (ReflectionUnionType::getName()).
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Call to undefined method ReflectionUnionType::getName()');

        $this->reflection('union')->getParamTypeAt();
    }

    /* -----------------------------------------------------------------
     |  getParamsNames()
     | -----------------------------------------------------------------
     */

    public function test_get_params_names_renvoie_les_noms_dans_l_ordre(): void
    {
        $this->assertSame(['name', 'age'], $this->reflection('typed')->getParamsNames());
    }

    public function test_get_params_names_renvoie_un_tableau_vide_sans_parametre(): void
    {
        $this->assertSame([], $this->reflection('noParameter')->getParamsNames());
    }

    public function test_get_params_names_liste_aussi_le_parametre_variadique(): void
    {
        $this->assertSame(['items'], $this->reflection('variadic')->getParamsNames());
    }

    /* -----------------------------------------------------------------
     |  Résolution de l'action
     | -----------------------------------------------------------------
     */

    public function test_une_action_invokable_est_reflechie(): void
    {
        $reflection = $this->reflection('__invoke');

        $this->assertSame(['id'], $reflection->getParamsNames());
        $this->assertSame(['id' => 'string'], $reflection->getParamsTypes());
    }

    public function test_une_methode_privee_est_lisible(): void
    {
        // ReflectionMethod ne respecte pas la visibilité pour la lecture des métadonnées.
        $this->assertSame(['secret' => 'string'], $this->reflection('privateAction')->getParamsTypes());
    }

    public function test_une_classe_inconnue_leve_une_reflection_exception(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class "Clicalmani\Core\Tests\Stubs\Absent" does not exist');

        new RequestReflection('Clicalmani\Core\Tests\Stubs\Absent', 'show');
    }

    public function test_une_methode_inconnue_leve_une_reflection_exception(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage(
            'Method ' . RequestReflectionControllerStub::class . '::missing() does not exist'
        );

        $this->reflection('missing');
    }
}
