<?php

namespace App\Events\Realtime;

/**
 * Base of the negotiation events. Offers are competing, private data: the offer cards go to the customer's own
 * channel, and a provider hears about its own offer on its own channel. The booking channel is shared with every
 * dispatched provider, so no offer data is ever sent there (Plan I10).
 */
abstract class OfferEvent extends PartyEvent {}
