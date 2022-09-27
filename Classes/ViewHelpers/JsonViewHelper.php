<?php
namespace  Nordkirche\NkcAddress\ViewHelpers;

use Nordkirche\Ndk\Domain\Model\Address;
use Nordkirche\Ndk\Domain\Model\ContactItem;
use Nordkirche\Ndk\Domain\Model\File\Image;
use Nordkirche\Ndk\Domain\Model\Institution\Institution;
use Nordkirche\Ndk\Domain\Model\Person\Person;
use Nordkirche\Ndk\Domain\Model\Person\PersonFunction;

class JsonViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('object', 'object', 'Address object', FALSE);
    }

    /**
     * @return string
     */
    public function render()
    {

        $object = $this->arguments['object'];

        if ($object === null) {
            $object = $this->renderChildren();
        }

        if ($object instanceof Person) {
            // Render person

            /** @var Person $person */
            $person = $object;

            $address = NULL;

            /** @var PersonFunction $function */
            foreach($person->getFunctions() as $function) {
                if ($function->getAddress() instanceof Address) {
                    $address = $function->getAddress();
                } elseif($function->getInstitution() instanceof Institution) {
                    $address = $function->getInstitution()->getAddress();
                }
                break;
            }

            if ($person->getPicture() instanceof Image) {
                $image = $person->getPicture()->render(600);
            } else {
                $image = '';
            }

            $result = [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'affiliation' => ($function instanceof PersonFunction) ? $this->getInstitutionData($function->getInstitution()) : '',
                'email' => $this->getContactItem($function, 'E-Mail'),
                'image' => $image,
                'jobTitle' =>  ($function instanceof PersonFunction) ? $function->getTitle() : '',
                'name' => $person->getName()->getFormatted(),
                'telephone' =>  ($function instanceof PersonFunction) ? $this->getContactItem($function, 'Telefon') : '',
                'url' =>  ($function instanceof PersonFunction) ? $this->getContactItem($function, 'Website') : '',
            ];

            if ($address instanceof Address) {
                $result['address'] = [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $address->getCity(),
                    'postalCode' => $address->getZipCode(),
                    'streetAddress' => $address->getStreet()
                ];
            }

            return json_encode($result);
        } elseif ($object instanceof Institution) {
            // Render institution
            return json_encode($this->getInstitutionData($object));
        } else {
            return json_encode([]);
        }
    }

    /**
     * @param Institution $institution
     * @return array
     */
    private function getInstitutionData($institution)
    {
        if ($institution->getLogo() instanceof Image) {
            $image = $institution->getLogo()->render(600);
        } elseif ($institution->getPicture() instanceof Image) {
            $institution->getPicture()->render(600);
        } else {
            $image = '';
        }

        $result = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $institution->getOfficialName(),
            'email' => $this->getContactItem($institution, 'E-Mail'),
            'image' => $image,
            'telephone' => $this->getContactItem($institution, 'Telefon'),
            'url' => $this->getContactItem($institution, 'Website'),
        ];

        if ($institution->getAddress() instanceof Address) {
            $result['address'] = [
                '@type' => 'PostalAddress',
                'addressLocality' => $institution->getAddress()->getCity(),
                'postalCode' => $institution->getAddress()->getZipCode(),
                'streetAddress' => $institution->getAddress()->getStreet()
            ];
        }
        return $result;
    }


    /**
     * @param $object
     * @param $type
     * @return string
     */
    private function getContactItem($object, $type)
    {
        /** @var ContactItem $contactItem */
        if ($object) {
            foreach($object->getContactItems() as $contactItem) {
                if ($contactItem->getType() == $type) {
                    return $contactItem->getValue();
                }
            }
        }
    }

}
