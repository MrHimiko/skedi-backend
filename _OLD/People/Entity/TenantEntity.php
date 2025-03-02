<?php

namespace App\Plugins\People\Entity;

use App\Plugins\Account\Entity\OrganizationEntity;
use App\Plugins\Account\Entity\UserEntity;
use App\Plugins\Countries\Entity\CountryEntity;
use App\Plugins\Storage\Entity\FileEntity;
use App\Plugins\Activity\Entity\LogEntity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use DateTimeInterface;

#[ORM\Entity(repositoryClass: "App\Plugins\People\Repository\TenantRepository")]
#[ORM\Table(name: "people_tenants")]
class TenantEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "tenant_id", type: "bigint")]
    private int $id;

    #[ORM\Column(name: "partition", type: "integer", options: ["default" => 1])]
    private int $partition = 1;

    #[ORM\ManyToOne(targetEntity: OrganizationEntity::class)]
    #[ORM\JoinColumn(name: "tenant_organization_id", referencedColumnName: "organization_id", nullable: false)]
    private OrganizationEntity $organization;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "tenant_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $user = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "tenant_updated_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $updatedUser = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "tenant_created_user_id", referencedColumnName: "user_id", nullable: false)]
    private UserEntity $createdUser;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(name: "tenant_assigned_user_id", referencedColumnName: "user_id", nullable: true)]
    private ?UserEntity $assignedUser = null;

    #[ORM\ManyToOne(targetEntity: FileEntity::class)]
    #[ORM\JoinColumn(name: "tenant_photo_id", referencedColumnName: "file_id", nullable: true)]
    private ?FileEntity $photo = null;

    #[ORM\Column(name: "tenant_first_name", type: "string", length: 255)]
    private string $firstName;

    #[ORM\Column(name: "tenant_middle_name", type: "string", length: 255, nullable: true)]
    private ?string $middleName = null;

    #[ORM\Column(name: "tenant_last_name", type: "string", length: 255)]
    private string $lastName;

    #[ORM\Column(name: "tenant_company_name", type: "string", length: 255, nullable: true)]
    private ?string $companyName = null;

    #[ORM\Column(name: "tenant_job_title", type: "string", length: 255, nullable: true)]
    private ?string $jobTitle = null;

    #[ORM\Column(name: "tenant_street_1_1", type: "string", length: 255, nullable: true)]
    private ?string $street11 = null;

    #[ORM\Column(name: "tenant_street_1_2", type: "string", length: 255, nullable: true)]
    private ?string $street12 = null;

    #[ORM\Column(name: "tenant_city_1", type: "string", length: 255, nullable: true)]
    private ?string $city1 = null;

    #[ORM\Column(name: "tenant_state_1", type: "string", length: 255, nullable: true)]
    private ?string $state1 = null;

    #[ORM\ManyToOne(targetEntity: CountryEntity::class)]
    #[ORM\JoinColumn(name: "tenant_country_id_1", referencedColumnName: "country_id", nullable: true)]
    private ?CountryEntity $country1 = null;

    #[ORM\Column(name: "tenant_zip_code_1", type: "string", length: 10, nullable: true)]
    private ?string $zipCode1 = null;

    #[ORM\Column(name: "tenant_street_2_1", type: "string", length: 255, nullable: true)]
    private ?string $street21 = null;

    #[ORM\Column(name: "tenant_street_2_2", type: "string", length: 255, nullable: true)]
    private ?string $street22 = null;

    #[ORM\Column(name: "tenant_city_2", type: "string", length: 255, nullable: true)]
    private ?string $city2 = null;

    #[ORM\Column(name: "tenant_state_2", type: "string", length: 255, nullable: true)]
    private ?string $state2 = null;

    #[ORM\ManyToOne(targetEntity: CountryEntity::class)]
    #[ORM\JoinColumn(name: "tenant_country_id_2", referencedColumnName: "country_id", nullable: true)]
    private ?CountryEntity $country2 = null;

    #[ORM\Column(name: "tenant_zip_code_2", type: "string", length: 10, nullable: true)]
    private ?string $zipCode2 = null;

    #[ORM\Column(name: "tenant_lead_status", type: "string", length: 255, nullable: true)]
    private ?string $leadStatus = null;

    #[ORM\Column(name: "tenant_lead_source", type: "string", length: 255, nullable: true)]
    private ?string $leadSource = null;

    #[ORM\Column(name: "tenant_lead_medium", type: "string", length: 255, nullable: true)]
    private ?string $leadMedium = null;

    #[ORM\Column(name: "tenant_credit_score", type: "decimal", precision: 3, scale: 0, nullable: true)]
    private ?int $creditScore = null;

    #[ORM\Column(name: "tenant_monthly_income", type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $monthlyIncome = null;

    #[ORM\Column(name: "tenant_search_min_bedrooms", type: "integer", nullable: true)]
    private ?int $searchMinBedrooms = null;

    #[ORM\Column(name: "tenant_search_min_bathrooms", type: "integer", nullable: true)]
    private ?int $searchMinBathrooms = null;

    #[ORM\Column(name: "tenant_search_max_rent", type: "decimal", precision: 10, scale: 2, nullable: true)]
    private ?float $searchMaxRent = null;

    #[ORM\Column(name: "tenant_search_move_in", type: "datetime", nullable: true)]
    private ?DateTimeInterface $searchMoveIn = null;

    #[ORM\Column(name: "tenant_welcome_sms", type: "boolean", options: ["default" => false])]
    private bool $welcomeSms = false;

    #[ORM\Column(name: "tenant_deleted", type: "boolean", options: ["default" => false])]
    private bool $deleted = false;

    #[ORM\Column(name: "tenant_prospect", type: "boolean", options: ["default" => true])]
    private bool $prospect = true;

    #[ORM\Column(name: "tenant_phones", type: "object_array", nullable: true)]
    private ?array $phones = [];

    #[ORM\Column(name: "tenant_emails", type: "object_array", nullable: true)]
    private ?array $emails = [];

    #[ORM\Column(name: "tenant_pets", type: "object_array", nullable: true)]
    private ?array $pets = [];

    #[ORM\Column(name: "tenant_vehicles", type: "object_array", nullable: true)]
    private ?array $vehicles = [];

    #[ORM\Column(name: "tenant_dependents", type: "object_array", nullable: true)]
    private ?array $dependents = [];

    #[ORM\Column(name: "tenant_updated", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $updated;

    #[ORM\Column(name: "tenant_created", type: "datetime", nullable: false, options: ["default" => "CURRENT_TIMESTAMP"])]
    private DateTimeInterface $created;

    public function __construct()
    {
        $this->updated = new DateTime();
        $this->created = new DateTime();
    }

    public function onLog(LogEntity $log, &$data)
    {
        if($this->getProspect())
        {
            $log->setProspect($this);

            $data['entity'] = 'People:Prospect';
        }
        else 
        {
            $log->setTenant($this);
        }
    }

    public function getLogName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPartition(): int
    {
        return $this->partition;
    }

    public function setPartition(int $partition): self
    {
        $this->partition = $partition;
        return $this;
    }

    public function getOrganization(): OrganizationEntity
    {
        return $this->organization;
    }

    public function setOrganization(OrganizationEntity $organization): self
    {
        $this->organization = $organization;
        return $this;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function setUser(?UserEntity $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUpdatedUser(): ?UserEntity
    {
        return $this->updatedUser;
    }

    public function setUpdatedUser(?UserEntity $updatedUser): self
    {
        $this->updatedUser = $updatedUser;
        return $this;
    }

    public function getCreatedUser(): UserEntity
    {
        return $this->createdUser;
    }

    public function setCreatedUser(UserEntity $createdUser): self
    {
        $this->createdUser = $createdUser;
        return $this;
    }

    public function getAssignedUser(): ?UserEntity
    {
        return $this->assignedUser;
    }

    public function setAssignedUser(?UserEntity $assignedUser): self
    {
        $this->assignedUser = $assignedUser;
        return $this;
    }

    public function getPhoto(): ?FileEntity
    {
        return $this->photo;
    }

    public function setPhoto(?FileEntity $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): self
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    public function setJobTitle(?string $jobTitle): self
    {
        $this->jobTitle = $jobTitle;
        return $this;
    }

    public function getStreet11(): ?string
    {
        return $this->street11;
    }

    public function setStreet11(?string $street11): self
    {
        $this->street11 = $street11;
        return $this;
    }

    public function getStreet12(): ?string
    {
        return $this->street12;
    }

    public function setStreet12(?string $street12): self
    {
        $this->street12 = $street12;
        return $this;
    }

    public function getCity1(): ?string
    {
        return $this->city1;
    }

    public function setCity1(?string $city1): self
    {
        $this->city1 = $city1;
        return $this;
    }

    public function getState1(): ?string
    {
        return $this->state1;
    }

    public function setState1(?string $state1): self
    {
        $this->state1 = $state1;
        return $this;
    }

    public function getCountry1(): ?CountryEntity
    {
        return $this->country1;
    }

    public function setCountry1(?CountryEntity $country1): self
    {
        $this->country1 = $country1;
        return $this;
    }

    public function getZipCode1(): ?string
    {
        return $this->zipCode1;
    }

    public function setZipCode1(?string $zipCode1): self
    {
        $this->zipCode1 = $zipCode1;
        return $this;
    }

    public function getStreet21(): ?string
    {
        return $this->street21;
    }

    public function setStreet21(?string $street21): self
    {
        $this->street21 = $street21;
        return $this;
    }

    public function getStreet22(): ?string
    {
        return $this->street22;
    }

    public function setStreet22(?string $street22): self
    {
        $this->street22 = $street22;
        return $this;
    }

    public function getCity2(): ?string
    {
        return $this->city2;
    }

    public function setCity2(?string $city2): self
    {
        $this->city2 = $city2;
        return $this;
    }

    public function getState2(): ?string
    {
        return $this->state2;
    }

    public function setState2(?string $state2): self
    {
        $this->state2 = $state2;
        return $this;
    }

    public function getCountry2(): ?CountryEntity
    {
        return $this->country2;
    }

    public function setCountry2(?CountryEntity $country2): self
    {
        $this->country2 = $country2;
        return $this;
    }

    public function getZipCode2(): ?string
    {
        return $this->zipCode2;
    }

    public function setZipCode2(?string $zipCode2): self
    {
        $this->zipCode2 = $zipCode2;
        return $this;
    }

    public function getLeadStatus(): ?string
    {
        return $this->leadStatus;
    }

    public function setLeadStatus(?string $leadStatus): self
    {
        $this->leadStatus = $leadStatus;
        return $this;
    }

    public function getLeadSource(): ?string
    {
        return $this->leadSource;
    }

    public function setLeadSource(?string $leadSource): self
    {
        $this->leadSource = $leadSource;
        return $this;
    }

    public function getLeadMedium(): ?string
    {
        return $this->leadMedium;
    }

    public function setLeadMedium(?string $leadMedium): self
    {
        $this->leadMedium = $leadMedium;
        return $this;
    }

    public function getCreditScore(): ?int
    {
        return $this->creditScore;
    }

    public function setCreditScore(?int $creditScore): self
    {
        $this->creditScore = $creditScore;
        return $this;
    }

    public function getMonthlyIncome(): ?float
    {
        return $this->monthlyIncome;
    }

    public function setMonthlyIncome(?float $monthlyIncome): self
    {
        $this->monthlyIncome = $monthlyIncome;
        return $this;
    }

    public function getSearchMinBedrooms(): ?int
    {
        return $this->searchMinBedrooms;
    }

    public function setSearchMinBedrooms(?int $searchMinBedrooms): self
    {
        $this->searchMinBedrooms = $searchMinBedrooms;
        return $this;
    }

    public function getSearchMinBathrooms(): ?int
    {
        return $this->searchMinBathrooms;
    }

    public function setSearchMinBathrooms(?int $searchMinBathrooms): self
    {
        $this->searchMinBathrooms = $searchMinBathrooms;
        return $this;
    }

    public function getSearchMaxRent(): ?float
    {
        return $this->searchMaxRent;
    }

    public function setSearchMaxRent(?float $searchMaxRent): self
    {
        $this->searchMaxRent = $searchMaxRent;
        return $this;
    }

    public function getSearchMoveIn(): ?DateTimeInterface
    {
        return $this->searchMoveIn;
    }

    public function setSearchMoveIn(?DateTimeInterface $searchMoveIn): self
    {
        $this->searchMoveIn = $searchMoveIn;
        return $this;
    }

    public function getWelcomeSms(): bool
    {
        return $this->welcomeSms;
    }

    public function setWelcomeSms(bool $welcomeSms): self
    {
        $this->welcomeSms = $welcomeSms;
        return $this;
    }

    public function getDeleted(): bool
    {
        return $this->deleted;
    }

    public function setDeleted(bool $deleted): self
    {
        $this->deleted = $deleted;
        return $this;
    }

    public function getProspect(): bool
    {
        return $this->prospect;
    }

    public function setProspect(bool $prospect): self
    {
        $this->prospect = $prospect;
        return $this;
    }

    public function getPhones(): ?array
    {
        return $this->phones;
    }

    public function setPhones(?array $phones): self
    {
        $this->phones = $phones;
        return $this;
    }

    public function getEmails(): ?array
    {
        return $this->emails;
    }

    public function setEmails(?array $emails): self
    {
        $this->emails = $emails;
        return $this;
    }

    public function getPets(): ?array
    {
        return $this->pets;
    }

    public function setPets(?array $pets): self
    {
        $this->pets = $pets;
        return $this;
    }

    public function getVehicles(): ?array
    {
        return $this->vehicles;
    }

    public function setVehicles(?array $vehicles): self
    {
        $this->vehicles = $vehicles;
        return $this;
    }

    public function getDependents(): ?array
    {
        return $this->dependents;
    }

    public function setDependents(?array $dependents): self
    {
        $this->dependents = $dependents;
        return $this;
    }

    public function getUpdated(): DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(DateTimeInterface $updated): self
    {
        $this->updated = $updated;
        return $this;
    }

    public function getCreated(): DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->getId(),
            'partition'          => $this->getPartition(),
            'organization'       => $this->getOrganization()->getId(),
            'user'               => $this->getUser() ? $this->getUser()->toArray() : null,
            'updatedUser'        => $this->getUpdatedUser() ? $this->getUpdatedUser()->toArray() : null,
            'createdUser'        => $this->getCreatedUser()->toArray(),
            'assignedUser'       => $this->getAssignedUser() ? $this->getAssignedUser()->getId() : null,
            'photo'              => $this->getPhoto()?->toArray(),
            'firstName'          => $this->getFirstName(),
            'middleName'         => $this->getMiddleName(),
            'lastName'           => $this->getLastName(),
            'companyName'        => $this->getCompanyName(),
            'jobTitle'           => $this->getJobTitle(),
            'street11'           => $this->getStreet11(),
            'street12'           => $this->getStreet12(),
            'city1'              => $this->getCity1(),
            'state1'             => $this->getState1(),
            'country1'           => $this->getCountry1() ? $this->getCountry1()->getId() : null,
            'zipCode1'           => $this->getZipCode1(),
            'street21'           => $this->getStreet21(),
            'street22'           => $this->getStreet22(),
            'city2'              => $this->getCity2(),
            'state2'             => $this->getState2(),
            'country2'           => $this->getCountry2() ? $this->getCountry2()->getId() : null,
            'zipCode2'           => $this->getZipCode2(),
            'leadStatus'         => $this->getLeadStatus(),
            'leadSource'         => $this->getLeadSource(),
            'leadMedium'         => $this->getLeadMedium(),
            'creditScore'        => $this->getCreditScore(),
            'monthlyIncome'      => $this->getMonthlyIncome(),
            'searchMinBedrooms'  => $this->getSearchMinBedrooms(),
            'searchMinBathrooms' => $this->getSearchMinBathrooms(),
            'searchMaxRent'      => $this->getSearchMaxRent(),
            'searchMoveIn'       => $this->getSearchMoveIn(),
            'welcomeSms'         => $this->getWelcomeSms(),
            'deleted'            => $this->getDeleted(),
            'prospect'           => $this->getProspect(),
            'phones'             => $this->getPhones(),
            'emails'             => $this->getEmails(),
            'pets'               => $this->getPets(),
            'vehicles'           => $this->getVehicles(),
            'dependents'         => $this->getDependents(),
            'updated'            => $this->getUpdated()->format('Y-m-d H:i:s'),
            'created'            => $this->getCreated()->format('Y-m-d H:i:s'),
        ];
    }
}
