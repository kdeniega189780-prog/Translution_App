<?php

namespace App\Http\Controllers;

use App\Application\BookingWorkflow;
use App\Http\Requests\AdvanceBookingRequest;
use App\Http\Requests\BookingRequest;
use App\Http\Requests\ConsentRequest;
use App\Http\Requests\DiagnosisRequest;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReasonRequest;
use App\Http\Requests\UsePartRequest;
use App\Models\Booking;
use App\Models\Part;
use App\Models\Service;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingWorkflow $workflow) {}

    public function index(ListRequest $r)
    {
        $query = Booking::visibleTo($r->user())->with(['customer', 'vehicle', 'service', 'staff'])->latest('preferred_at')->orderByDesc('id');
        if ($r->routeIs('jobs.index')) {
            $query->where('status', 'processing');
        }
        if ($r->filled('status')) {
            $query->where('status', $r->status);
        }
        if ($r->filled('date')) {
            $query->whereDate('scheduled_at', $r->date);
        }
        if ($r->filled('q')) {
            $q = $r->q;
            $query->where(fn ($b) => $b->whereHas('vehicle', fn ($v) => $v->where('plate_number', 'like', "%$q%"))->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%$q%")));
        }

        return view('bookings.index', ['bookings' => $query->paginate(config('workshop.table_page_size'))->withQueryString()->fragment('bookings-list')]);
    }

    public function create(Request $r)
    {
        return view('bookings.create', ['vehicles' => Vehicle::with('customer')->whereHas('customer', fn ($q) => $r->user()->role === 'customer' ? $q->where('user_id', $r->user()->id) : $q)->orderBy('plate_number')->get(), 'services' => Service::where('active', true)->get()]);
    }

    public function store(BookingRequest $r)
    {
        $b = $this->workflow->create($r->user(), $r->validated());

        return to_route('bookings.show', $b)->with('status', 'Booking requested. The shop will confirm your schedule.');
    }

    public function show(Booking $booking)
    {
        $this->authorize('view', $booking);

        return view('bookings.show', ['booking' => $booking->load(['customer', 'vehicle', 'service', 'staff', 'parts.part', 'invoice']), 'mechanics' => User::where('role', 'mechanic')->where('active', true)->get(), 'parts' => Part::orderBy('name')->get()]);
    }

    public function advance(AdvanceBookingRequest $r, Booking $booking)
    {
        $this->workflow->advance($r->user(), $booking, $r->validated());

        return back()->with('status', 'Booking moved to the next step.');
    }

    public function diagnose(DiagnosisRequest $r, Booking $booking)
    {
        $this->workflow->diagnose($r->user(), $booking, $r->validated());

        return back()->with('status', 'Diagnosis saved. Customer approval is required.');
    }

    public function consent(ConsentRequest $r, Booking $booking)
    {
        $this->workflow->consent($r->user(), $booking, $r->validated());

        return back()->with('status', 'Customer decision recorded.');
    }

    public function usePart(UsePartRequest $r, Booking $booking)
    {
        $this->workflow->usePart($r->user(), $booking, $r->validated());

        return back()->with('status', 'Part recorded and stock deducted.');
    }

    public function reverse(ReasonRequest $r, Booking $booking)
    {
        $this->workflow->reverse($r->user(), $booking, $r->reason);

        return back()->with('status', 'Previous step restored. The reason was recorded.');
    }

    public function destroy(Request $r, Booking $booking)
    {
        $this->workflow->archive($r->user(), $booking);

        return to_route('bookings.index')->with('status', 'Booking archived.');
    }
}
