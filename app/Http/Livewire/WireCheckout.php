<?php

namespace App\Http\Livewire;

use App\Mail\InvoiceMail;
use App\Models\Address;
use App\Models\Article;
use App\Models\Deal;
use App\Models\Delivery;
use App\Models\Invoice;
use App\Models\Parameter;
use App\Models\User;
use Carbon\Carbon;
use Gloudemans\Shoppingcart\Facades\Cart;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Mail;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;
use Livewire\WithFileUploads;

class WireCheckout extends Component
{
    use WithFileUploads, LivewireAlert;

    public $setting;

    public $categories;

    public $sous_category;

    public $articles;

    public $price_delivery;

    public $invoice;

    public $valeur;

    public $modes;

    public $mode = 175;

    public $address;

    public $relay;

    public $addresses;

    public $cities;

    public $title;

    public $subtitle;

    public $city;

    public $location;

    public $step = 1;

    public $payments;

    public $relays;

    public $payment = 53;

    public $selectedOtherCity;

    public function mount()
    {
        $this->invoice = user_cart(Cookie::get('customer'))->cart->first();
        //dd($this->invoice->toArray());
        if (! $this->invoice) {
            toast('Panier vide', 'warning')->autoClose(15000);
            redirect()->route('checkout.cart');
        }
        journalisation('chechout', $this->invoice);
        $this->setting = setting();
        $this->categories = Parameter::where('type_parameter_id', 17)
            ->orderByRaw('rank asc, created_at desc')
            ->whereNull('parent_id')
            ->get();
        $this->modes = Parameter::where([
            'type_parameter_id' => 21,
        ])
            ->orderBy('title', 'asc')
            ->get();
        $this->payments = Parameter::where([
            'type_parameter_id' => 23,
        ])
            ->orderBy('title', 'asc')
            ->get();
        $this->cities = Parameter::where([
            'type_parameter_id' => 2,
        ])
        ->orderByRaw('rank asc, title asc')
        ->get();

        $this->relays = Article::where([
            'rubric_id' => 263,
        ])
            ->orderBy('title', 'asc')
            ->get();

        $this->articles = all_articles();
    }

    public function render()
    {
        $this->price_delivery = coutLivraison(Cart::instance('shopping')->subtotal());
        $this->invoice = user_cart(Cookie::get('customer'))->cart->first();
        $this->addresses = Address::where([
            'user_id' => auth()->user()->id,
        ])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.checkout.index')
            ->extends('layouts.app', [
                'title' => 'Mode de livraison',
                'setting' => $this->setting,
                'categories' => $this->categories,
                'articles' => $this->articles,
                /* 'cities' => Parameter::where([
                'type_parameter_id' => 2,
            ])
            ->orderBy('title', 'asc')
            ->get(), */
            ]);
    }

    public function updatedselectedOtherCity($cities_id)
    {

        $this->cities = Parameter::where([
            'type_parameter_id' => 2,
        ])
            ->orderBy('title', 'asc')
            ->get();

    }

    // Choix du mode de livraison
    public function modeNext()
    {
        //dd($this->mode);
        $this->validate([
            'mode' => 'required',
        ]);
        $user = user_cart(Cookie::get('customer'));
        if (count($user->cart) == 0) {
            //dd(count($user->cart));
            /* $this->alert('error', 'Utilisateur non valide', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
                'showCancelButton' => true,
                'cancelButtonText' => 'Fermer',
                'onDismissed' => '',
                'timerProgressBar' => true,
            ]); */
            toast('Panier vide', 'warning')->autoClose(15000);
            redirect()->route('checkout.cart');
        } else {
            $this->invoice->update([
                'delivery_mode_id' => $this->mode,
                'planned_at' => Carbon::now()->addDay(),
                'user_updated' => auth()->user()->id,
            ]);
            $this->alert('success', 'Choix du mode de livraison', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
                'showCancelButton' => true,
                'cancelButtonText' => 'Fermer',
                'onDismissed' => '',
                'timerProgressBar' => true,
            ]);
            journalisation('Choix mode livraison', $this->invoice);
            //redirect()->route('checkout.address');
        }
    }

    // Choix de l'adresse de livraison
    public function addressNext()
    {
        $this->validate([
            'address' => 'required',
        ]);
        $user = user_cart(Cookie::get('customer'));
        if (count($user->cart) == 0) {
            toast('Panier vide', 'warning')->autoClose(15000);
            redirect()->route('checkout.cart');
        } else {
            $suppliers = $this->invoice->articles
                ->groupBy('supplier_id');
            $end = Address::find($this->address)->city_id;
            //dd($end);
            $amount = 0;
            foreach ($suppliers as $key => $value) {
                $start = User::find($key)->city_id;
                $delivery = Delivery::where([
                    'start_id' => $start,
                    'end_id' => $end,
                ])
                ->first();
                if ($delivery) {
                    $amount += $delivery->amount;
                }
                //dd($start, $end, $amount);
            }
            $price_final = $this->invoice->price_ht + $amount;
            $this->invoice->update([
                'address_id' => $this->address,
                'planned_at' => Carbon::now()->addDay(),
                'price_delivery' => $amount,
                'price_final' => $price_final,
                'user_updated' => auth()->user()->id,
            ]);
            $this->alert('success', 'Choix de l\'adresse de livraison', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
                'showCancelButton' => true,
                'cancelButtonText' => 'Fermer',
                'onDismissed' => '',
                'timerProgressBar' => true,
            ]);
            journalisation('Choix adresse', $this->invoice);
            //redirect()->route('checkout.address');
        }
    }

    // Choix du pont de relais
    public function relayNext()
    {
        $this->validate([
            'relay' => 'required',
        ]);
        $user = user_cart(Cookie::get('customer'));
        if (count($user->cart) == 0) {
            toast('Panier vide', 'warning')->autoClose(15000);
            redirect()->route('checkout.cart');
        } else {
            $this->invoice->update([
                'relay_id' => $this->relay,
                'user_updated' => auth()->user()->id,
            ]);
            $this->alert('success', 'Choix du point de relais', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
                'showCancelButton' => true,
                'cancelButtonText' => 'Fermer',
                'onDismissed' => '',
                'timerProgressBar' => true,
            ]);
            journalisation('Choix du point de relais', $this->invoice);
            //redirect()->route('checkout.address');
        }
    }

    // Choix du moyen de paiement
    public function paymentNext()
    {
        $this->validate([
            'payment' => 'required',
        ]);
        $user = user_cart(Cookie::get('customer'));
        if (count($user->cart) == 0) {
            toast('Panier vide', 'warning')->autoClose(15000);
            redirect()->route('checkout.cart');
        } else {
            $this->invoice->update([
                'payment_method_id' => $this->payment,
                'planned_at' => Carbon::now()->addDay(),
                'user_updated' => auth()->user()->id,
            ]);
            $this->alert('success', 'Choix du moyen de paiement', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
                'showCancelButton' => true,
                'cancelButtonText' => 'Fermer',
                'onDismissed' => '',
                'timerProgressBar' => true,
            ]);
            journalisation('Choix du moyen de paiement', $this->invoice);
            //redirect()->route('checkout.address');
        }
    }

    // Suppression du mode de livraison afin de pouvoir modifier
    public function modeDelete()
    {
        $this->invoice->update([
            'delivery_mode_id' => null,
            'address_id' => null,
            'price_delivery' => null,
            'planned_at' => null,
            'user_updated' => auth()->user()->id,
        ]);
        journalisation('Suppression du mode livraison', $this->invoice);
    }

    // Suppression de l'adresse de livraison afin de pouvoir modifier
    public function addressDelete()
    {
        $this->invoice->update([
            'address_id' => null,
            'price_delivery' => null,
            'planned_at' => null,
            'user_updated' => auth()->user()->id,
        ]);
        journalisation('Suppression de l\'adresse livraison', $this->invoice);
    }

    // Suppression du moyen de paiement afin de pouvoir modifier
    public function relayDelete()
    {
        $this->invoice->update([
            'relay_id' => null,
            'price_delivery' => null,
            'planned_at' => null,
            'user_updated' => auth()->user()->id,
        ]);
        journalisation('Suppression du moyen de paiement', $this->invoice);
    }

    // Suppression du moyen de paiement afin de pouvoir modifier
    public function paymentDelete()
    {
        $this->invoice->update([
            'payment_method_id' => null,
            'price_delivery' => null,
            'planned_at' => null,
            'user_updated' => auth()->user()->id,
        ]);
        journalisation('Suppression du moyen de paiement', $this->invoice);
    }

    private function resetInputFields()
    {
        $this->title = null;
        $this->subtitle = null;
        $this->city = null;
        $this->location = null;
    }

    public function edit($address)
    {
        $this->valeur = $this->addresses->filter(function ($value) use ($address) {
            return $value->id == $address;
        })
            ->first();
        //dd($this->valeur->toArray());
        journalisation('address openModal edit');
        $this->title = $this->valeur->title;
        $this->subtitle = $this->valeur->subtitle;
        $this->city = $this->valeur->city_id;
        $this->location = $this->valeur->location;
    }

    public function destroy()
    {
        $this->valeur->delete();
        $this->alert('success', 'Suppression effectuée avec succès', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
            'showCancelButton' => true,
            'cancelButtonText' => 'Fermer',
            'onDismissed' => '',
            'timerProgressBar' => true,
        ]);
        $this->resetInputFields();
        $this->emit('formClose');
    }

    public function openModal()
    {
        $this->resetInputFields();
    }

    public function addressStore()
    {
         $this->validate([
            'title' => 'required',
            'subtitle' => 'required',
            'city' => 'required',
        ]);

        if ($this->city == 270) {
            $this->validate([
                /* 'title' => 'required',
                'subtitle' => 'required',
                'city' => 'required', */
                'location' => 'required',
            ]);
        }

        if ($this->valeur) {

            if ($this->city == 270) {

                $this->valeur->update([
                    'title' => $this->title,
                    'subtitle' => $this->subtitle,
                    'city_id' => $this->city,
                    'location' => $this->location,

                    'user_updated' => auth()->user()->id,
                ]);
            } else{
                $this->valeur->update([
                    'title' => $this->title,
                    'subtitle' => $this->subtitle,
                    'city_id' => $this->city,

                    'user_updated' => auth()->user()->id,
                ]);
            }

            $message = 'Modification effectuée avec succès';
        } else {
            if ($this->city == 270) {
                Address::create([
                    'title' => $this->title,
                    'subtitle' => $this->subtitle,
                    'city_id' => $this->city,
                    'location' => $this->location,
                    'user_id' => auth()->user()->id,


                ]);
            }else{
                Address::create([
                    'title' => $this->title,
                    'subtitle' => $this->subtitle,
                    'city_id' => $this->city,
                    'user_id' => auth()->user()->id,
                ]);
            }
            $message = 'Ajout effectué avec succès';
        }

        $this->alert('success', $message, [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
            'showCancelButton' => true,
            'cancelButtonText' => 'Fermer',
            'onDismissed' => '',
            'timerProgressBar' => true,
        ]);
        $this->resetInputFields();
        $this->emit('formClose');
    }

    public function addressEdit($address)
    {
        $this->valeur = $this->addresses->filter(function ($value) use ($address) {
            return $value->id == $address;
        })
            ->first();
        //dd($this->valeur->toArray());
        journalisation('address openModal edit');
        $this->title = $this->valeur->title;
        $this->subtitle = $this->valeur->subtitle;
        $this->city = $this->valeur->city_id;
        $this->location = $this->valeur->location;
    }

    public function addressDestroy()
    {
        $this->valeur->delete();
        $this->alert('success', 'Suppression effectuée avec succès', [
            'position' => 'top-end',
            'timer' => 3000,
            'toast' => true,
            'showCancelButton' => true,
            'cancelButtonText' => 'Fermer',
            'onDismissed' => '',
            'timerProgressBar' => true,
        ]);
        $this->resetInputFields();
        $this->emit('formClose');
    }

    public function confirmer()
    {
        $suppliers = $this->invoice->articles
        ->groupBy('supplier_id');
        //dd($suppliers->first()->toArray());

        if (count($suppliers) > 1) {
            foreach ($suppliers as $key => $value) {
                $amount = delivery_cost($this->invoice->address_id, $key);
                //dd($amount);
                $quantity = $value->sum('pivot.quantity');
                $price_ht = $value->sum('pivot.price_total');
                //$benefit = $value->sum('pivot.benefit');
                //$price_delivery = coutLivraison($price_ht);
                $price_final = $price_ht + $amount;
                //dd($quantity, $price_ht, $price_delivery, $price_final, $suppliers->toArray());
                $invoice = Invoice::create([
                    'type' => 'product',
                    'quantity' => $quantity,
                    'price_ht' => $price_ht,
                    //'benefit' => $benefit,
                    'price_final' => $price_final,
                    'customer_id' => auth()->user()->id,
                    'state_id' => 48,
                    'ip' => request()->ip(),
                    'planned_at' => Carbon::now()->addDay(),
                    'price_delivery' => $amount,
                    'user_created' => auth()->user()->id,

                    'relay_id' => $this->invoice->relay_id,
                    'delivery_mode_id' => $this->invoice->delivery_mode_id,
                    'payment_method_id' => $this->invoice->payment_method_id,
                    'address_id' => $this->invoice->address_id,
                    'supplier_id' => $key,
                    'parent_id' => $this->invoice->id,
                ]);
                $invoice->states()->attach(48, [
                    'user_created' => auth()->user()->id,
                    'status' => 1,
                ]);
                $board = 0;
                foreach ($value as $article) {
                    if ($article->supplier_id == $key) {
                        if ($article and $article->available_id == 54) {
                            $benefit = ($article->board/100) * ($article->pivot->price * $article->pivot->quantity);
                            $board += $benefit;
                            $invoice->articles()->attach($article->id, [
                                'price' => $article->pivot->price,
                                'price_total' => $article->pivot->price * $article->pivot->quantity,
                                'quantity' => $article->pivot->quantity,
                                'user_id' => $article->pivot->user_id,
                                'board' => $article->board,
                                'benefit' => $benefit,
                                'options' => $article->pivot->options, //pas gérer
                            ]);
                        }
                    }
                }
                $invoice->update([
                    'benefit' => $board,
                ]);
                $this->invoice->states()->updateExistingPivot(47, [
                    'user_updated' => auth()->user()->id,
                    'status' => null,
                ]);
                $this->invoice->states()->attach(264, [
                    'user_created' => auth()->user()->id,
                    'status' => 1,
                ]);
                $this->invoice->update([
                    'state_id' => 264,
                ]);
                // Notifier le fournisseur
                Mail::to(auth()->user()->email)->send(new InvoiceMail($invoice, 'fournisseur'));
            }
            //$this->invoice->delete();
        } else {
            $invoice = $this->invoice;
            $quantity = count(Cart::instance('shopping')->content());
            $price_ht = Cart::instance('shopping')->subtotal();
            //$price_delivery = coutLivraison($price_ht);
            //dd($invoice->toArray());
            //dd($this->invoice->address_id);
            $end = Address::find($this->invoice->address_id)->city_id;
            $amount = 0;
            $supplier_id = null;
            foreach ($suppliers as $key => $value) {
                $start = User::find($key)->city_id;
                $delivery = Delivery::where([
                    'start_id' => $start,
                    'end_id' => $end,
                ])
                ->first();
                if ($delivery) {
                    $amount += $delivery->amount;
                }
                $supplier_id = $key;
            }
            $price_final = $price_ht + $amount;
            // Si on déjà une commande en cours. On fait une mise à jour et on continue
            $invoice->update([
                'type' => 'product',
                'quantity' => $quantity,
                'price_ht' => $price_ht,
                'planned_at' => Carbon::now()->addDay(),
                'price_delivery' => $amount,
                'price_final' => $price_final,
                'customer_id' => Cookie::get('customer'),
                'user_updated' => auth()->user()->id,
                'created_at' => Carbon::now(),
                'supplier_id' => $supplier_id,
                'state_id' => 48,
            ]);
            //pivot_invoice_MAJ($invoice);
            $invoice->articles()->detach();
            $board = 0;
            foreach ($suppliers->first() as $article) {
                if ($article->supplier_id == $key) {
                    if ($article and $article->available_id == 54) {
                        $benefit = ($article->board/100) * ($article->pivot->price * $article->pivot->quantity);
                        $board += $benefit;
                        $invoice->articles()->attach($article->id, [
                            'price' => $article->pivot->price,
                            'price_total' => ($article->pivot->price * $article->pivot->quantity),
                            'quantity' => $article->pivot->quantity,
                            'user_id' => $article->pivot->user_id,
                            'board' => $article->board,
                            'benefit' => $benefit,
                            'options' => $article->pivot->options, //pas gérer
                        ]);
                    }
                }
            }
            $invoice->update([
                'benefit' => $board,
            ]);
            $this->invoice->states()->updateExistingPivot(47, [
                'user_updated' => auth()->user()->id,
                'status' => null,
            ]);
            $this->invoice->states()->attach(48, [
                'user_created' => auth()->user()->id,
                'status' => 1,
            ]);
            // Notifier le fournisseur
            Mail::to(auth()->user()->email)->send(new InvoiceMail($invoice, 'fournisseur'));
        }
        // Diminuer la quantité de l'article disponible
        foreach (Cart::instance('shopping')->content() as $item) {
            $article = detailPanier($item->id);
            if ($article and $article->available_id == 54) {

                Deal::create([
                    'type' => 'sortie',
                    'quantity' => $item->qty,
                    'price' => $article->price_new,
                    'price_total' => $article->price_new * $item->qty,
                    'article_id' => $article->id,
                    'invoice_id' => $this->invoice->id,
                ]);

                if ($article->active_size) {
                    $size = Parameter::whereTitle($item->options->size)->first();
                    if ($size) {
                        $article->sizes()->updateExistingPivot($size->id, [
                            'quantity' => $item->qty,
                        ]);
                    }
                }
                else {
                    $article->update([
                        'quantity' => $article->quantity - $item->qty,
                    ]);
                }
            }
        }
        // Notifier le client
        Mail::to(auth()->user()->email)->send(new InvoiceMail($this->invoice, 'client'));
        // Notifier l'adminitrateur
        foreach ($this->setting->email as $recipient) {
            Mail::to($recipient)->send(new InvoiceMail($invoice, 'administrateur'));
        }
        journalisation('confirmer', $this->invoice);
        Cart::instance('shopping')->destroy();
        Cookie::queue(Cookie::forget('custom'));
        toast('Félicitation ', 'success')->autoClose(10000);
        redirect()->route('checkout.congrat', $invoice->code);
    }
}