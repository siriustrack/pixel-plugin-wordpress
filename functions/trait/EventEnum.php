<?php

class EventEnum extends BaseEnum
{
    public const PAGE_VIEW             = 'PageView';
    public const LEAD                  = 'Lead';
    public const VIEW_CONTENT          = 'ViewContent';
    public const CONTENT               = 'Content';
    public const ADD_TO_WISHLIST       = 'AddToWishlist';
    public const ADD_TO_CART           = 'AddToCart';
    public const ADD_PAYMENT_INFO      = 'AddPaymentInfo';
    public const INITIATE_CHECKOUT     = 'InitiateCheckout';
    public const START_TRIAL           = 'StartTrial';
    public const SUBSCRIBE             = 'Subscribe';
    public const PURCHASE              = 'Purchase';
    public const CONTACT               = 'Contact';
    public const COMPLETE_REGISTRATION = 'CompleteRegistration';
    public const SUBMIT_APPLICATION    = 'SubmitApplication';
    public const SEARCH                = 'Search';
    public const SCHEDULE              = 'Schedule';
    public const REFUND                = 'Refund';
    public const SUBSCRIBE_CANCELED    = 'SubscribeCanceled';
    public const CUSTOM                = 'Custom';

    protected static function labels(): array
    {
        return [
            self::PAGE_VIEW             => __('PageView - Visualização de Página', 'pixel-x-app'),
            self::LEAD                  => __('Lead - Cadastro', 'pixel-x-app'),
            self::VIEW_CONTENT          => __('ViewContent - Visualizar Conteúdo Chave (Um por página)', 'pixel-x-app'),
            self::CONTENT               => __('Content - Visualizar Conteúdo Complementar (Evento Personalizado)', 'pixel-x-app'),
            self::ADD_TO_WISHLIST       => __('AddToWishlist - Adicionar a Lista de Desejo', 'pixel-x-app'),
            self::ADD_TO_CART           => __('AddToCart - Adicionar ao Carrinho', 'pixel-x-app'),
            self::ADD_PAYMENT_INFO      => __('AddPaymentInfo - Adicionar Informações de Pagamento', 'pixel-x-app'),
            self::INITIATE_CHECKOUT     => __('InitiateCheckout - Iniciar Finalização da Compra', 'pixel-x-app'),
            self::START_TRIAL           => __('StartTrial - Iniciar período de avaliação', 'pixel-x-app'),
            self::SUBSCRIBE             => __('Subscribe - Assinou/Renovou um Plano de Assinatura', 'pixel-x-app'),
            self::PURCHASE              => __('Purchase - Compra Confirmada', 'pixel-x-app'),
            self::CONTACT               => __('Contact - Entrar em contato', 'pixel-x-app'),
            self::COMPLETE_REGISTRATION => __('CompleteRegistration - Cadastro Completo', 'pixel-x-app'),
            self::SUBMIT_APPLICATION    => __('SubmitApplication - Enviou uma Solicitação ou Aplicação', 'pixel-x-app'),
            self::SEARCH                => __('Search - Pesquisa no Site', 'pixel-x-app'),
            self::SCHEDULE              => __('Schedule - Fez uma Reserva ou Agendamento', 'pixel-x-app'),
            self::REFUND                => __('Refund - Fez ou solicitou reembolso (Evento Personalizado)', 'pixel-x-app'),
            self::SUBSCRIBE_CANCELED    => __('SubscribeCanceled - Cancelou uma assinatura (Evento Personalizado)', 'pixel-x-app'),
            self::CUSTOM                => __('Evento Personalizado', 'pixel-x-app'),
        ];
    }

    public static function custom($value)
    {
        return [
            'abandoned_cart'      => self::INITIATE_CHECKOUT,
            'waiting_payment'     => self::ADD_PAYMENT_INFO,
            'billet_pix_generate' => self::ADD_PAYMENT_INFO,
            'trial'               => self::START_TRIAL,
            'approved'            => self::PURCHASE,
            'refund'              => self::REFUND,
            'subscribe'           => self::SUBSCRIBE,
            'canceled'            => self::SUBSCRIBE_CANCELED,
        ][$value] ?? self::LEAD;
    }

    public static function ticto($value)
    {
        // authorized: Compra Aprovada
        // refused: Compra negada no cartão
        // delayed: Boleto atrasado
        // refunded:Reembolso Efetuado blocked: Bloqueio de Anti-Fraude
        // expired: Boleto ou pix vencido
        // abandoned_cart: Abandono de Carrinho
        // trial: Período de Testes
        // waiting_payment: Aguardando Pagamento
        // subscription_canceled: Assinatura Cancelada
        return [
            'abandoned_cart'    => self::INITIATE_CHECKOUT,
            'waiting_payment'   => self::ADD_PAYMENT_INFO,
            'bank_slip_created' => self::ADD_PAYMENT_INFO,
            'pix_created'       => self::ADD_PAYMENT_INFO,
            'authorized'        => self::PURCHASE,
            'trial'             => self::START_TRIAL,
            'all_charges_paid'  => self::SUBSCRIBE,

            // 'refused'           => self::CANCELED,
            // 'delayed'           => self::DELAYED,
            // 'refunded'          => self::REFUNDED,
            // 'expired'           => self::EXPIRED,
        ][$value] ?? self::LEAD;
    }

    public static function kiwify($value)
    {
        return [
            'abandoned'       => self::INITIATE_CHECKOUT,
            'abandoned_cart'  => self::INITIATE_CHECKOUT,
            'waiting_payment' => self::ADD_PAYMENT_INFO,
            'paid'            => self::PURCHASE,
            'refunded'        => self::REFUND,
            'chargedback'     => self::REFUND,
        ][$value] ?? self::LEAD;
    }

    public static function hotmart($value)
    {
        return [
            'PURCHASE_OUT_OF_SHOPPING_CART' => self::INITIATE_CHECKOUT,
            'PURCHASE_BILLET_PRINTED'       => self::ADD_PAYMENT_INFO,
            'PURCHASE_APPROVED'             => self::PURCHASE,
            'PURCHASE_REFUNDED'             => self::REFUND,
            'PURCHASE_CHARGEBACK'           => self::REFUND,

            'PRINTED_BILLET'  => self::ADD_PAYMENT_INFO,
            'APPROVED'        => self::PURCHASE,
            'PRE_ORDER'       => self::START_TRIAL,
            'STARTED'         => self::ADD_PAYMENT_INFO,
            'WAITING_PAYMENT' => self::ADD_PAYMENT_INFO,

            // 'PURCHASE_BILLET_PRINTED'       => self::ADD_PAYMENT_INFO,
            // 'PURCHASE_APPROVED'             => self::PURCHASE,
            // BLOCKED
            // CANCELLED
            // CHARGEBACK
            // COMPLETE
            // EXPIRED
            // NO_FUNDS
            // OVERDUE
            // PARTIALLY_REFUNDED
            // PROCESSING_TRANSACTION
            // PROTESTED
            // REFUNDED
            // STARTED
            // UNDER_ANALISYS
            // WAITING_PAYMENT
        ][$value] ?? self::LEAD;
    }

    public static function eduzz($value)
    {
        return [
            'cart_abandonment'        => self::INITIATE_CHECKOUT,
            'open'                    => self::ADD_PAYMENT_INFO,
            'invoice_open'            => self::ADD_PAYMENT_INFO,
            'waiting_payment'         => self::ADD_PAYMENT_INFO,
            'invoice_waiting_payment' => self::ADD_PAYMENT_INFO,
            'invoice_paid'            => self::PURCHASE,
            'paid'                    => self::PURCHASE,
            'invoice_trial'           => self::START_TRIAL,
            'refunded'                => self::REFUND,
        ][$value] ?? self::LEAD;
    }

    public static function blitzpay($value)
    {
        return [
            'abandoned_cart' => self::INITIATE_CHECKOUT,
            'generate'       => self::ADD_PAYMENT_INFO,
            'approved'       => self::PURCHASE,
            // 'completed'
            // 'reversal'
            // 'failed'
            // 'chargeback'
        ][$value] ?? self::LEAD;
    }

    public static function celetus($value)
    {
        return [
            'pending'                  => self::INITIATE_CHECKOUT,
            'AbandonedCheckout'        => self::INITIATE_CHECKOUT,
            'BoletoGenerated'          => self::ADD_PAYMENT_INFO,
            'PixGenerated'             => self::ADD_PAYMENT_INFO,
            'WaitingPayment'           => self::ADD_PAYMENT_INFO,
            'Approved'                 => self::PURCHASE,
            'ApprovedPurchase'         => self::PURCHASE,
            'ApprovedPurchaseComplete' => self::PURCHASE,
            // 'completed'
            // 'reversal'
            // 'failed'
            // 'chargeback'
        ][$value] ?? self::LEAD;
    }

    public static function dmg($value)
    {
        return [
            'abandoned'       => self::INITIATE_CHECKOUT,
            'started'         => self::ADD_PAYMENT_INFO,
            'billet_printed'  => self::ADD_PAYMENT_INFO,
            'waiting_payment' => self::ADD_PAYMENT_INFO,
            'trial'           => self::START_TRIAL,
            'approved'        => self::PURCHASE,
            'active'          => self::SUBSCRIBE,
            'chargeback'      => self::REFUND,
            'reversal'        => self::REFUND,
            // 'completed'
            // 'failed'
        ][$value] ?? self::LEAD;
    }

    public static function braip($value)
    {
        return [
            0 => self::INITIATE_CHECKOUT,
            1 => self::ADD_PAYMENT_INFO,
            2 => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function monetizze($value)
    {
        return [
            7 => self::INITIATE_CHECKOUT,
            1 => self::ADD_PAYMENT_INFO,
            2 => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function doppus($value)
    {
        return [
            'exit_checkout' => self::INITIATE_CHECKOUT,
            'waiting'       => self::ADD_PAYMENT_INFO,
            'approved'      => self::PURCHASE,
            'testing'       => self::START_TRIAL,
            'reversed'      => self::REFUND,
            'refunded'      => self::REFUND,
            'chargeback'    => self::REFUND,
        ][$value] ?? self::LEAD;
    }

    public static function zouti($value)
    {
        return [
            'processing'      => self::INITIATE_CHECKOUT,
            'waiting_payment' => self::ADD_PAYMENT_INFO,
            'paid'            => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function hubla($value)
    {
        return [
            'AbandonedCheckout' => self::INITIATE_CHECKOUT,
            'PendingSale'       => self::ADD_PAYMENT_INFO,
            'NewSale'           => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function cartpanda($value)
    {
        return [
            // 'AbandonedCheckout' => self::INITIATE_CHECKOUT,
            'order.created' => self::ADD_PAYMENT_INFO,
            'order.paid'    => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function payt($value)
    {
        return [
            'lost_cart'              => self::INITIATE_CHECKOUT,
            'waiting_payment'        => self::ADD_PAYMENT_INFO,
            'paid'                   => self::PURCHASE,
            'canceled'               => self::REFUND,
            'subscription_activated' => self::SUBSCRIBE,
        ][$value] ?? self::LEAD;
    }

    public static function green($value)
    {
        return [
            'abandoned'       => self::INITIATE_CHECKOUT,
            'created'         => self::INITIATE_CHECKOUT,
            'waiting_payment' => self::ADD_PAYMENT_INFO,
            'pending_payment' => self::ADD_PAYMENT_INFO,
            'trialing'        => self::START_TRIAL,
            'paid'            => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function perfectpay($value)
    {
        return [
            12 => self::INITIATE_CHECKOUT,
            1  => self::ADD_PAYMENT_INFO,
            2  => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function lastlink($value)
    {
        return [
            'Abandoned_Cart'             => self::INITIATE_CHECKOUT,
            'Purchase_Request_Confirmed' => self::ADD_PAYMENT_INFO,
            'Purchase_Order_Confirmed'   => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function kirvano($value)
    {
        return [
            'ABANDONED_CART'      => self::INITIATE_CHECKOUT,
            'BANK_SLIP_GENERATED' => self::ADD_PAYMENT_INFO,
            'PIX_GENERATED'       => self::ADD_PAYMENT_INFO,
            'SALE_APPROVED'       => self::PURCHASE,
            'SALE_CHARGEBACK'     => self::REFUND,
            'SALE_REFUNDED'       => self::REFUND,
        ][$value] ?? self::LEAD;
    }

    public static function zippify($value)
    {
        return [
            'abandoned'       => self::INITIATE_CHECKOUT,
            'waiting_payment' => self::ADD_PAYMENT_INFO,
            'paid'            => self::PURCHASE,
            'refunded'        => self::REFUND,
        ][$value] ?? self::LEAD;
    }

    public static function yampi($value)
    {
        return [
            'cart.reminder' => self::INITIATE_CHECKOUT,
            'order.created' => self::ADD_PAYMENT_INFO,
            'order.paid'    => self::PURCHASE,
        ][$value] ?? self::LEAD;
    }

    public static function cakto($value)
    {
        return [
            'boleto_gerado'         => self::ADD_PAYMENT_INFO,
            'pix_gerado'            => self::ADD_PAYMENT_INFO,
            'purchase_approved'     => self::PURCHASE,
            'purchase_refused'      => self::REFUND,
            'refund'                => self::REFUND,
            'chargeback'            => self::REFUND,
            'subscription_canceled' => self::SUBSCRIBE_CANCELED,
            'subscription_renewed'  => self::SUBSCRIBE,
            'checkout_abandonment'  => self::INITIATE_CHECKOUT,
        ][$value] ?? self::LEAD;
    }

    public static function voomp($value)
    {
        return [
            // Venda
            'checkoutAbandoned' => self::INITIATE_CHECKOUT,
            'paid'              => self::PURCHASE,
            'refused'           => self::ADD_PAYMENT_INFO,
            'refunded'          => self::REFUND,
            'chargedback'       => self::REFUND,
            'waiting_payment'   => self::ADD_PAYMENT_INFO,

            // Assinatura
            'trialing'        => self::START_TRIAL,
            'pending_payment' => self::ADD_PAYMENT_INFO,
            'canceled'        => self::SUBSCRIBE_CANCELED,
            // 'unpaid'=>
        ][$value] ?? self::LEAD;
    }
}
