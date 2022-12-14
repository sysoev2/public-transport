<?php

namespace App\Controller;

use App\Entity\TransportType;
use App\Form\TransportTypeType;
use App\Repository\TransportTypeRepository;
use App\Service\FormsErrorManager;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/transports/types', name: 'api_transport_type_', requirements: ['id' => '\d+'])]
class TransportTypeController extends AbstractController
{

    private TransportTypeRepository $transportTypeRepository;
    private EntityManagerInterface $em;
    private Serializer $serializer;

    public function __construct(TransportTypeRepository $transportTypeRepository, EntityManagerInterface $em)
    {
        $this->transportTypeRepository = $transportTypeRepository;
        $this->em = $em;
        $this->serializer = SerializerBuilder::create()->setPropertyNamingStrategy(
            new SerializedNameAnnotationStrategy(
                new IdenticalPropertyNamingStrategy()
            ))->build();
    }

    #[Route('/', name: 'get_all', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW');
        $data = $this->transportTypeRepository->findAll();
        $data = $this->serializer->toArray($data, context: SerializationContext::create()->setGroups(['TRANSPORT_PUBLIC']));
        return $this->json($data);
    }

    #[Route('/{id}', name: 'get', methods: ['GET'])]
    public function get($id): JsonResponse
    {
        $this->denyAccessUnlessGranted('VIEW');
        $data = $this->transportTypeRepository->find($id);
        if(!is_null($data)) {
            $data = $this->serializer->toArray($data, context: SerializationContext::create()->setGroups(['TRANSPORT_PUBLIC']));
            return $this->json($data);
        }
        return $this->json(['message' => 'The type does not exist'], Response::HTTP_NOT_FOUND);
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('CREATE');
        $data = json_decode($request->getContent(), true);
        $type = new TransportType();
        $form = $this->createForm(TransportTypeType::class, $type);

        try {
            $form->submit($data);
        } catch (\Throwable) {
            return $this->json(
                ['message'=> 'The data is incorrect or is not full'],
                Response::HTTP_BAD_REQUEST
            );
        }
        if ($form->isValid()) {
            $this->em->persist($type);
            $this->em->flush();
            $type = $this->serializer
                ->toArray(
                    $type,
                    context: SerializationContext::create()->setGroups(['TRANSPORT_PUBLIC'])
                );
            return $this->json($type);
        } else {
            return $this->json(
                ['message'=> 'The data is incorrect'],
                Response::HTTP_BAD_REQUEST
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $this->denyAccessUnlessGranted('DELETE');
        $type = $this->transportTypeRepository->find($id);
        if (!is_null($type)) {
            $this->transportTypeRepository->remove($type, true);
            return $this->json(['message' => 'The type is deleted']);
        }
        return $this->json(['message' => 'The type does not exist'], Response::HTTP_NOT_FOUND);
    }

    #[Route('/{id}', name: 'update', methods: ['PATCH', 'PUT'])]
    public function update(Request $request, FormsErrorManager $formsErrorManager, $id): JsonResponse
    {
        $this->denyAccessUnlessGranted('UPDATE');
        $data = json_decode($request->getContent(), true);
        $type = $this->transportTypeRepository->find($id);
        if(!is_null($type)) {
            $form = $this->createForm(TransportTypeType::class, $type);
            $clearMissing = $request->getMethod() != 'PATCH';
            try {
                $form->submit($data, $clearMissing);
            } catch (\Throwable $exception) {
                return $this->json(
                    ['message'=> 'The data is incorrect or is not full'],
                    Response::HTTP_BAD_REQUEST
                );
            }
            if ($form->isValid()) {
                $this->em->flush();
                $type = $this->serializer
                    ->toArray(
                        $type,
                        context: SerializationContext::create()->setGroups(['TRANSPORT_PUBLIC'])
                    );
                return $this->json($type);
            } else {
                return $this->json([
                    'message' => $formsErrorManager->getErrorsFromForm($form)
                ], Response::HTTP_BAD_REQUEST);
            }
        }
        return $this->json(['message' => 'The type does not exist'], Response::HTTP_NOT_FOUND);
    }
}
