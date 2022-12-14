<?php

namespace App\Tests\Transport;

use App\Entity\Transport;
use App\Entity\TransportType;
use App\Entity\User;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Hautelook\AliceBundle\PhpUnit\RefreshDatabaseTrait;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Nelmio\Alice\Loader\NativeLoader;

class TransportTest extends WebTestCase
{
    use RefreshDatabaseTrait;

    private KernelBrowser $client;
    private EntityManager $em;

    protected function setUp(): void
    {
        $loader = new NativeLoader();
        $this->client = self::createClient();
        $kernel = static::bootKernel();
        $objectSet = $loader->loadFile($kernel->getProjectDir() . '/fixtures/data.yaml');
        $this->em = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        $this->client->setServerParameter('HTTP_x-api-token', '11111111111111111111111111111111111111111111111111111111111111111111111111111111111111');

    }


    public function testCreate(): void
    {
        $typeId = $this->em->getRepository(TransportType::class)->findOneBy([])->getId();
        $this->client->request('POST', '/api/transports/', content: json_encode([
            'number' => 1001,
            'type' => $typeId
        ]));
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('number', $data);
        $this->assertSame(1001, $data['number']);
        $this->assertArrayHasKey('type', $data);
        $this->assertSame($typeId, $data['type']['id']);
    }


    public function testGetAll(): void
    {
        $this->client->request('GET', '/api/transports/');
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertCount(12, $data);
        $this->assertArrayHasKey(0, $data);
        $this->assertArrayHasKey('number', $data[0]);

        $this->assertArrayHasKey('type', $data[0]);
    }

    public function testGet(): void
    {
        $transportId = $this->em->getRepository(Transport::class)->findOneBy(['number' => 1001])->getId();
        $this->client->request('GET', '/api/transports/' . $transportId);
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('number', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($transportId, $data['id']);
        $this->assertSame(1001, $data['number']);
    }

    public function testPatch(): void
    {
        $transportId = $this->em->getRepository(Transport::class)->findOneBy(['number' => 1001])->getId();
        $this->client->request('PATCH', '/api/transports/' . $transportId, content: json_encode([
            'number' => 1002
        ]));
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('number', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($transportId, $data['id']);
        $this->assertSame(1002, $data['number']);
    }

    public function testPut(): void
    {
        $transport = $this->em->getRepository(Transport::class)->findOneBy(['number' => 1002]);
        $this->client->request('PUT', '/api/transports/' . $transport->getId(), content: json_encode([
            'number' => 1001,
            'type' => $transport->getType()->getId()
        ]));
        $response = $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('number', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertArrayHasKey('id', $data);
        $this->assertSame($transport->getId(), $data['id']);
        $this->assertSame(1001, $data['number']);
    }

    public function testDelete(): void
    {
        $transport = $this->em->getRepository(Transport::class)->findOneBy(['number' => 1001]);
        $this->client->request('DELETE', '/api/transports/' . $transport->getId());
        $this->assertResponseIsSuccessful();
    }

    public function testCreateFail(): void
    {
        $typeId = $this->em->getRepository(TransportType::class)->findOneBy([])->getId();
        $this->client->request('POST', '/api/transports/', content: json_encode([
            'number' => null,
            'type' => $typeId
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testGetFail(): void
    {
        $this->client->request('GET', '/api/transports/0');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUpdateNotFound()
    {
        $this->client->request('PATCH', '/api/transports/0');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testPatchFail(): void
    {
        $transportId = $this->em->getRepository(Transport::class)->findOneBy([])->getId();
        $this->client->request('PATCH', '/api/transports/' . $transportId, content: json_encode([
            'number' => 'fail'
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testPutFailInvalid(): void
    {
        $transport = $this->em->getRepository(Transport::class)->findOneBy([]);
        $this->client->request('PUT', '/api/transports/' . $transport->getId(), content: json_encode([
            'number' => 'fail',
            'type' => $transport->getType()->getId()
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testPutFail400(): void
    {
        $transport = $this->em->getRepository(Transport::class)->findOneBy([]);
        $this->client->request('PUT', '/api/transports/' . $transport->getId(), content: json_encode([
            'number' => 1222,
        ]));
        $this->assertResponseStatusCodeSame(400);
    }

    public function testDeleteFail(): void
    {
        $this->client->request('DELETE', '/api/transports/0');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testGetFavorites(): void
    {
        $this->client->request('GET', '/api/transports/favorites');
        $this->assertResponseIsSuccessful();
        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);
        $favorites = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@tt.com'])->getTransports();
        $serializer = SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(
                new IdenticalPropertyNamingStrategy()
            ))->build();
        $favorites = $serializer
            ->toArray(
                $favorites,
                context: SerializationContext::create()->setGroups(['TRANSPORT_PUBLIC'])
            );
        $this->assertEquals($data, $favorites);
    }

    public function testAddFavorite(): void
    {
        $transports = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@tt.com'])->getTransports();
        $transports = $transports->map(function ($obj) {
            return $obj->getId();
        })->getValues();
        $criteria = new Criteria();
        $criteria->where(Criteria::expr()->notIn('id', $transports));
        $transport = $this->em->getRepository(Transport::class)->matching($criteria)->first();

        $this->client->request('POST', '/api/transports/favorites', content: json_encode([
            'transport' => $transport->getId()
        ]));
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(202);
    }

    public function testRemoveFavorite(): void
    {
        $transport = $this->em->getRepository(User::class)
            ->findOneBy(['email' => 'admin@tt.com'])
            ->getTransports()
            ->first();
        $this->client->request('DELETE', '/api/transports/favorites/' . $transport->getId());
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(202);

    }

    public function testAddFavoriteNotFound(): void
    {
        $this->client->request('POST', '/api/transports/favorites', content: json_encode([
            'transport' => 0
        ]));
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRemoveFavoriteNotFound(): void
    {
        $this->client->request('DELETE', '/api/transports/favorites/0');
        $this->assertResponseStatusCodeSame(404);

    }
}
