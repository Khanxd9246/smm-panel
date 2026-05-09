<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
<<<<<<< HEAD
    protected $fillable = ['user_id','amount','type','description','status','reference'];
    protected $casts    = ['amount'=>'float'];
    public function user(): BelongsTo  { return $this->belongsTo(User::class); }
    public function scopeCompleted($q) { return $q->where('status','completed'); }
=======
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'status',
        'reference',
        'gateway',
        'fund_account_id',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fundAccount(): BelongsTo
    {
        return $this->belongsTo(FundAccount::class);
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', 'completed');
    }
>>>>>>> 491ed81 (initial commit)
}
