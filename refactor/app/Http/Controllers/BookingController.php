<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $authenticatedUser = $request->__authenticatedUser;

        if ($authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $response = $this->repository->getAll($request);
        } else {
            $userId = $request->get('user_id');
            $response = $this->repository->getUsersJobs($userId);
        }
    
        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
       
        $user = $request->__authenticatedUser;
        $data = $request->all();
        $response = $this->repository->store($user, $data);
        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update(Request $request, $id)
{
    $data = $request->except(['_token', 'submit']);
    $user = $request->__authenticatedUser;
    $response = $this->repository->updateJob($id, $data, $user);
    
    return response($response);
}

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');
        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        $userId = $request->input('user_id');
        if ($userId) {
            $response = $this->repository->getUsersJobsHistory(
                $userId,
                $request
            );
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;
        $response = $this->repository->cancelJobAjax($data, $user);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->endJob($data);
        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->customerNotCall($data);
        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;
        $response = $this->repository->getPotentialJobs($user);
        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $jobId = $data['jobid'];
        $distance = $data['distance'] ?? null;
        $time = $data['time'] ?? null;
        $session = $data['session_time'] ?? null;
        $flagged = $data['flagged'] ?? false;
        $adminComment = $data['admincomment'] ?? null;
        $manuallyHandled = $data['manually_handled'] ?? false;
        $byAdmin = $data['by_admin'] ?? false;

        if ($flagged && empty($adminComment)) {
            return response('Please add comment', 400);
        }

        $distanceUpdated = false;
        if ($time || $distance) {
            $distanceUpdated = Distance::where('job_id', '=', $jobid)->update([
                'distance' => $distance,
                'time' => $time,
            ]);
        }

        $jobUpdated = false;
        if (
            $admincomment ||
            $session ||
            $flagged ||
            $manually_handled ||
            $by_admin
        ) {
            $jobUpdated = Job::where('id', '=', $jobid)->update([
                'admin_comments' => $admincomment,
                'flagged' => $flagged ? 'yes' : 'no',
                'session_time' => $session,
                'manually_handled' => $manually_handled ? 'yes' : 'no',
                'by_admin' => $by_admin ? 'yes' : 'no',
            ]);
        }

        if (!$distanceUpdated && !$jobUpdated) {
            return response('Nothing to update', 400);
        }

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        try {
            $response = $this->repository->reopen($data);
            return response($response);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resendNotifications(Request $request)
    {
        $jobId = $request->input('jobid');
        $job = $this->repository->find($jobId);
        $job_data = $this->repository->jobToData($job);
        try {
            $this->repository->sendNotificationTranslator($job, $job_data, '*');
            return response()->json(['success' => 'Push sent']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $jobId = $request->input('jobid');
        $job = $this->repository->find($jobId);
        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response()->json(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
