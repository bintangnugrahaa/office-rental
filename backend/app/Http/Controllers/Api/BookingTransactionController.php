<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingTransactionRequest;
use App\Http\Resources\Api\BookingTransactionResource;
use App\Http\Resources\Api\ViewBookingResource;
use App\Models\BookingTransaction;
use App\Models\OfficeSpace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Exception;

class BookingTransactionController extends Controller
{
    /**
     * Mengambil detail booking berdasarkan nomor telepon dan ID transaksi.
     */
    public function booking_details(Request $request): ViewBookingResource|JsonResponse
    {
        $request->validate([
            'phone_number'   => 'required|string',
            'booking_trx_id' => 'required|string',
        ]);

        // REFACTOR: Menggunakan firstOrFail untuk penanganan 'not found' yang lebih bersih.
        // Laravel akan secara otomatis merespon dengan 404 Not Found jika data tidak ada.
        $booking = BookingTransaction::where('phone_number', $request->phone_number)
            ->where('booking_trx_id', $request->booking_trx_id)
            ->with(['officeSpace', 'officeSpace.city'])
            ->firstOrFail();

        return new ViewBookingResource($booking);
    }

    /**
     * Menyimpan transaksi booking baru.
     */
    public function store(StoreBookingTransactionRequest $request): BookingTransactionResource
    {
        $validatedData = $request->validated();

        // REFACTOR: Menggunakan findOrFail untuk memastikan office space ada.
        // Jika tidak ditemukan, akan otomatis menghasilkan response 404.
        $officeSpace = OfficeSpace::findOrFail($validatedData['office_space_id']);

        $validatedData['is_paid'] = false;
        $validatedData['booking_trx_id'] = BookingTransaction::generateUniqueTrxId();
        $validatedData['duration'] = $officeSpace->duration;
        $validatedData['ended_at'] = (new \DateTime($validatedData['started_at']))
            ->modify("+{$officeSpace->duration} days");

        $bookingTransaction = BookingTransaction::create($validatedData);

        // REFACTOR: Memindahkan logika pengiriman notifikasi ke method terpisah.
        $this->sendBookingNotification($bookingTransaction);

        // REFACTOR: Memindahkan return statement ke akhir fungsi agar semua logika dijalankan.
        return new BookingTransactionResource($bookingTransaction->load('officeSpace'));
    }

    /**
     * Mengirim notifikasi SMS setelah booking berhasil dibuat.
     *
     * @param BookingTransaction $bookingTransaction
     */
    private function sendBookingNotification(BookingTransaction $bookingTransaction): void
    {
        // REFACTOR: Menggunakan blok try-catch untuk menangani kegagalan pengiriman SMS.
        // Kegagalan SMS tidak seharusnya mengagalkan seluruh proses booking (respons 500).
        try {
            // REFACTOR: Menggunakan helper config() yang merupakan praktik terbaik di Laravel
            // daripada getenv() secara langsung.
            $sid    = config('services.twilio.sid');
            $token  = config('services.twilio.token');
            $from   = config('services.twilio.from');

            // Pastikan SID, Token, dan nomor pengirim ada sebelum melanjutkan.
            if (!$sid || !$token || !$from) {
                Log::error('Twilio credentials are not configured.');
                return;
            }

            $twilio = new Client($sid, $token);

            // REFACTOR: Pesan notifikasi bisa dibuat lebih dinamis.
            $messageBody = "Booking Anda untuk office di {$bookingTransaction->officeSpace->name} dengan ID: {$bookingTransaction->booking_trx_id} telah berhasil dibuat.";

            $twilio->messages->create(
                "+{$bookingTransaction->phone_number}", // To
                [
                    "body" => $messageBody,
                    "from" => $from,
                ]
            );
        } catch (Exception $e) {
            // REFACTOR: Mencatat error jika pengiriman gagal untuk debugging.
            Log::error('Twilio SMS sending failed: ' . $e->getMessage());
        }
    }
}
