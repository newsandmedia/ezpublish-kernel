<?php
/**
 * File containing the eZ\Publish\API\Repository\URLAliasService class.
 *
 * @copyright Copyright (C) 1999-2012 eZ Systems AS. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 * @version //autogentag//
 * @package eZ\Publish\API\Repository
 */

namespace eZ\Publish\API\Repository\Tests\Stubs;

use eZ\Publish\API\Repository\URLAliasService;
use eZ\Publish\API\Repository\Values\Content\URLAlias;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\API\Repository\Values\Content\VersionInfo;

/**
 * URLAlias service
 *
 * @example Examples/urlalias.php
 *
 * @package eZ\Publish\API\Repository
 */
class URLAliasServiceStub implements URLAliasService
{
    /**
     * Repository
     *
     * @var eZ\Publish\API\Repository\Tests\Stubs\RepositoryStub
     */
    private $repository;

    /**
     * URL aliases
     *
     * @var eZ\Publish\API\Repository\Values\URLAlias
     */
    private $aliases = array();

    /**
     * Next ID to give to a new alias
     *
     * @var int
     */
    private $nextAliasId = 0;

    /**
     * Creates a new URLServiceStub
     *
     * @param RepositoryStub $repository
     * @return void
     */
    public function __construct( RepositoryStub $repository )
    {
        $this->repository = $repository;
        $this->initFromFixture();
    }

     /**
     * Create a user chosen $alias pointing to $location in $languageCode.
     *
     * This method runs URL filters and transformers before storing them.
     * Hence the path returned in the URLAlias Value may differ from the given.
     * $alwaysAvailable makes the alias available in all languages.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param string $path
     * @param boolean $forward if true a redirect is performed
     * @param string $languageCode the languageCode for which this alias is valid
     * @param boolean $alwaysAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the path already exists for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function createUrlAlias( Location $location, $path, $languageCode, $forwarding = false, $alwaysAvailable = false )
    {
        $this->checkAliasNotExists( $path, $languageCode );

        $data = array(
            'destination'     => $location,
            'path'            => $path,
            'languageCodes'   => array( $languageCode ),
            'alwaysAvailable' => $alwaysAvailable,
            'forward'         => $forwarding,
        );

        return $this->createLocationUrlAlias( $data );
    }

    /**
     * Creates an internal URL alias (autogeneration on publish)
     *
     * @param Location $location
     * @param string $path
     * @param string $languageCode
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    private function createInternalUrlAlias( Location $location, $path, $languageCode )
    {
        $this->checkAliasNotExists( $path, $languageCode );

        return $this->createLocationUrlAlias(
            array(
                'destination'   => $location,
                'path'          => $path,
                'languageCodes' => array( $languageCode ),
                'isCustom'      => false,
            )
        );
    }

    /**
     * Marks the given alias as being historical.
     *
     * @param \eZ\Public\API\Repository\Values\Content\URLAlias $alias
     * @return \eZ\Public\API\Repository\Values\Content\URLAlias
     */
    private function obsoleteAlias( URLAlias $alias )
    {
        $this->purgeObsoleteAliases( $alias->path );

        $this->removeAliases( array( $alias ) );

        $this->createLocationUrlAlias(
            array(
                'id'              => $alias->id,
                'type'            => $alias->type,
                'destination'     => $alias->destination,
                'path'            => $alias->path,
                'languageCodes'   => $alias->languageCodes,
                'alwaysAvailable' => $alias->alwaysAvailable,
                'isCustom'        => $alias->isCustom,
                'forward'         => $alias->forward,

                'isHistory'       => true,
            )
        );
    }

    /**
     * Purges history aliases for $path.
     *
     * @param string $path
     * @return void
     */
    private function purgeObsoleteAliases( $path )
    {
        foreach ( $this->aliases as $id => $existingAlias )
        {
            if ( $existingAlias->path == $path && $existingAlias->isHistory )
            {
                unset( $this->aliases[$id] );
            }
        }
    }

    /**
     * Creates a location URL alias from the given $properties
     *
     * @param array $properties
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    private function createLocationUrlAlias( array $properties )
    {
        $properties = array_merge(
            array(
                'id'              => ++$this->nextAliasId,
                'type'            => URLAlias::LOCATION,
                'isHistory'       => false,
                'isCustom'        => true,
                'alwaysAvailable' => true,
                'forward'         => true,
            ),
            $properties
        );
        return ( $this->aliases[$properties['id']] = new URLAlias( $properties ) );
    }

    /**
     * Checks if an alias for the given $path already exists.
     *
     * @param string $path
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the path already exists for the given language
     *
     * @return void
     */
    protected function checkAliasNotExists( $path, $languageCode )
    {
        foreach ( $this->aliases as $existingAlias )
        {
            if ( !$existingAlias->isHistory
                && $existingAlias->path == $path
                && in_array( $languageCode, $existingAlias->languageCodes ) )
            {
                throw new Exceptions\InvalidArgumentExceptionStub(
                    sprintf(
                        'An alias for path "%s" in language "%s" already exists.',
                        $path,
                        $languageCode
                    )
                );
            }
        }
    }

     /**
     * Create a user chosen $alias pointing to a resource in $languageName.
     *
     * This method does not handle location resources - if a user enters a location target
     * the createCustomUrlAlias method has to be used.
     * This method runs URL filters and and transformers before storing them.
     * Hence the path returned in the URLAlias Value may differ from the given.
     *
     * $alwaysAvailable makes the alias available in all languages.
     *
     * @param string $resource
     * @param string $path
     * @param boolean $forwarding
     * @param string $languageName
     * @param boolean $alwaysAvailable
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\InvalidArgumentException if the path already exists for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function createGlobalUrlAlias( $resource, $path, $languageCode, $forward = false, $alwaysAvailable = false )
    {
        $this->checkAliasNotExists( $path, $languageCode );

        $data = array(
            'id'              => ++$this->nextAliasId,
            'type'            => URLAlias::RESOURCE,
            'destination'     => $resource,
            'path'            => $path,
            'languageCodes'   => array( $languageCode ),
            'alwaysAvailable' => $alwaysAvailable,
            'isHistory'       => false,
            'isCustom'        => true,
            'forward'         => $forward,
        );
        return ( $this->aliases[$data['id']] = new URLAlias( $data ) );
    }

     /**
     * List of url aliases pointing to $location.
     *
     * @todo may be there is a need for a function which returns one URL Alias based on a prioritized language
     *
     * @param \eZ\Publish\API\Repository\Values\Content\Location $location
     * @param $custom if true the user generated aliases are listed otherwise the autogenerated
     * @param string $languageCode filters those which are valid for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias[]
     */
    public function listLocationAliases( Location $location, $custom = true, $languageCode = null )
    {
        $locationAliases = array();
        foreach ( $this->aliases as $existingAlias )
        {
            // Filter chain
            if ( !( $existingAlias->destination instanceof Location ) || $existingAlias->destination->id != $location->id )
            {
                continue;
            }
            if ( !$custom && $existingAlias->isCustom )
            {
                continue;
            }
            if ( $languageCode !== null && !in_array( $languageCode, $existingAlias->languageCodes ) )
            {
                continue;
            }

            $locationAliases[] = $existingAlias;
        }
        if ( !count( $locationAliases ) && $languageCode !== '' )
        {
            $locationAliases = $this->listLocationAliases( $location, $custom, '' );;
        }
        return $locationAliases;
    }

    /**
     * List global aliases
     *
     * @param string $languageCode filters those which are valid for the given language
     * @param int $offset
     * @param int $limit
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias[]
     */
    public function listGlobalAliases( $languageCode = null, $offset = 0, $limit = -1 )
    {
        $globalAliases = array();
        foreach ( $this->aliases as $existingAlias )
        {
            if ( !is_string( $existingAlias->destination ) )
            {
                continue;
            }
            if ( $languageCode !== null && !in_array( $languageCode, $existingAlias->languageCodes ) )
            {
                continue;
            }
            $globalAliases[] = $existingAlias;
        }

        return array_slice( $globalAliases, $offset, ( $limit == -1 ? null : $limit ) );
    }


    /**
     * Removes urls aliases.
     *
     * This method does not remove autogenerated aliases for locations.
     *
     * @param \eZ\Publish\API\Repository\Values\Content\URLAlias[] $aliasList
     * @return boolean
     */
    public function removeAliases( array $aliasList )
    {
        foreach ( $aliasList as $aliasToRemove )
        {
            foreach ( $this->aliases as $index => $existingAlias )
            {
                if ( $aliasToRemove == $existingAlias )
                {
                    // TODO: How to detect "autogenerated" aliases?
                    unset( $this->aliases[$index] );
                }
            }
        }
        return true;
    }

    /**
     * looks up the URLAlias for the given url.
     *
     *
     * @param string $url
     * @param string $languageCode
     *
     * @throws \eZ\Publish\API\Repository\Exceptions\NotFoundException if the path does not exist or is not valid for the given language
     *
     * @return \eZ\Publish\API\Repository\Values\Content\URLAlias
     */
    public function lookUp( $url, $languageCode = null )
    {
        foreach ( $this->aliases as $existingAlias )
        {
            if ( $existingAlias->path == $url
                && ( $languageCode === null || in_array( $languageCode, $existingAlias->languageCodes ) ) )
            {
                return $existingAlias;
            }
        }
        throw new Exceptions\NotFoundExceptionStub(
            sprintf(
                'No alias for URL "%s" in language "%s" could be found.',
                $url,
                $languageCode
            )
        );
    }

    /**
     * Auto-generates the URL aliases for $versionInfo
     *
     * ATTENTION: This method is not part of the Public API but is only used
     * internally in this implementation.
     *
     * @param \eZ\Publish\API\Repository\Content\VersionInfo $versionInfo
     * @return void
     */
    public function _createAliasesForVersion( VersionInfo $versionInfo )
    {
        $locationService = $this->repository->getLocationService();

        $locations = $locationService->loadLocations(
            $versionInfo->getContentInfo()
        );

        foreach ( $locations as $location )
        {
            $this->_obsoleteOldAliases( $location );
            $this->_createAliasesForLocation( $location );
        }
    }

    /**
     * Auto-generates aliases for the given $location
     *
     * Old aliases will automatically be moved to history mode.
     *
     * ATTENTION: This method is not part of the Public API but is only used
     * internally in this implementation.
     *
     * @param Location $location
     * @return void
     */
    public function _createAliasesForLocation( Location $location )
    {
        $contentService = $this->repository->getContentService();
        $content        = $contentService->loadContent(
            $location->getContentInfo()->id
        );

        if ( $content->getVersionInfo()->status !== VersionInfo::STATUS_PUBLISHED )
        {
            // Skip not yet published content
            return;
        }

        $versionInfo = $content->getVersionInfo();

        $this->_obsoleteOldAliases( $location );

        foreach ( $versionInfo->getNames() as $languageCode => $name )
        {
            $this->createInternalUrlAlias(
                $location,
                $this->createUrlAliasPath( $location, $name, $languageCode ),
                $languageCode
            );
        }
    }

    /**
     * Removes aliases for the given $location
     *
     * Does not move them to history mode, but actually deletes them.
     *
     * ATTENTION: This method is not part of the Public API but is only used
     * internally in this implementation.
     *
     * @param Location $location
     * @return void
     */
    public function _removeAliasesForLocation( Location $location )
    {
        $this->removeAliases( $this->listLocationAliases( $location ) );
    }

    /**
     * Creates the path for an alias to $location with $name in $languageCode
     *
     * @param Location $location
     * @param mixed $name
     * @param mixed $languageCode
     * @return string
     */
    private function createUrlAliasPath( Location $location, $name, $languageCode )
    {
        $locationService = $this->repository->getLocationService();

        $parentAliases = $this->listLocationAliases(
            $locationService->loadLocation( $location->parentLocationId ),
            false
        );

        $parentAlias = $this->guessCorrectParent( $parentAliases, $languageCode );


        $parentAlias = reset( $parentAliases );
        return $parentAlias->path . '/' . $this->generateAliasName( $name );
    }

    /**
     * Guesses the correct parent alias from $parentAliases for $languageCode
     *
     * Performs the following steps:
     *
     * 1. Checks for alias with $languageCode and returns on success
     * 2. Checks for alias with $alwaysAvailable and returns on success
     * 3. Chooses the first alias from $parentAliases
     * 4. Throws exception if $parentAliases is empty
     *
     * @param mixed $parentAliases
     * @param mixed $languageCode
     * @return void
     */
    private function guessCorrectParent( $parentAliases, $languageCode )
    {
        if ( !count( $parentAliases ) )
        {
            throw new \RuntimeException( "No parent aliases found." );
        }

        foreach ( $parentAliases as $potentialParent )
        {
            if ( in_array( $languageCode, $potentialParent->languageCodes ) )
            {
                return $potentialParent;
            }
        }

        foreach ( $parentAliases as $potentialParent )
        {
            if ( $potentialParent->alwaysAvailable )
            {
                return $potentialParent;
            }
        }

        return reset( $parentAliases );
    }

    /**
     * Generates a URLAlias path element for $name.
     *
     * Highly simplified.
     *
     * @param string $name
     * @return string
     * @todo Need to use the configured URL transformation here, as soon as the
     *       SPI and implementation are available.
     */
    private function generateAliasName( $name )
    {
        return strtr( $name, ' ', '-' );
    }

    /**
     * Deprecates old aliases of $location
     *
     * @param Location $location
     * @return void
     */
    public function _obsoleteOldAliases( Location $location )
    {
        $aliases = $this->listLocationAliases( $location );
        foreach ( $aliases as $alias )
        {
            $this->obsoleteAlias( $alias );
        }
    }

    /**
     * Internal helper method to emulate a rollback.
     *
     * @return void
     */
    public function __rollback()
    {
        $this->initFromFixture();
    }

    /**
     * Helper method that initializes some default data from an existing legacy
     * test fixture.
     *
     * @return void
     */
    private function initFromFixture()
    {
        $this->aliases     = array();
        $this->nextAliasId = 0;

        list(
            $aliases,
            $this->nextAliasId
        ) = $this->repository->loadFixture( 'URLAlias' );

        foreach ( $aliases as $alias )
        {
            $this->aliases[$alias->id] = $alias;
            $this->nextAliasId         = max( $this->nextAliasId, $alias->id );
        }
    }
}
