<style>
    h4 {
        color:#434343;
    }
</style>

<div class="well bs-component">
    <form class="form-horizontal" id="check_tx_form">
        <h4><i class="fa fa-check-square-o fa-fw"></i>Check Payment</h4>
        <fieldset>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="transaction_hash" style="margin-bottom: 10px;">Transaction Hash</label>
                    <input class="form-control" placeholder="64 char transaction id" id="transaction_hash">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="private_key" style="margin-bottom: 10px;">Private View Key</label>
                    <input class="form-control" placeholder="64 char private view key" id="private_key">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="public_address">Address</label>
                    <input class="form-control" placeholder="NERVA address" id="public_address">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <button class="btn btn-default" type="button" id="check_transaction">
                        <span class="fa fa-search"></span> Check Payment
                    </button>
                </div>
            </div>
        </fieldset>
    </form>
</div>

<div class="well bs-component">
    <form class="form-horizontal" id="validate_address_form">
        <h4><i class="fa fa-check-square-o fa-fw" aria-hidden="true"></i>Validate Address</h4>
        <fieldset>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="public_address">Address</label>
                    <input class="form-control" placeholder="NERVA address" id="validate_public_address">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <button class="btn btn-default" type="button" id="validate_address">
                        <span class="fa fa-search"></span> Validate
                    </button>
                </div>
            </div>
        </fieldset>
    </form>
</div>

<div class="well bs-component">
    <form class="form-horizontal" id="create_integrated_address_form">
        <h4><i class="fa fa-check-square-o fa-fw" aria-hidden="true"></i>Create Integrated Address
        </h4>
        <fieldset>
            <div class="col-md-12">
                <div class="form-group">
                    <label for="public_address">Address</label>
                    <input class="form-control" placeholder="NERVA address" id="integrated_public_address">
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <button class="btn btn-default" type="button" id="create_int_addr">
                        <span class="fa fa-search"></span> Create
                    </button>
                </div>
            </div>
        </fieldset>
    </form>
</div>

<div class="well bs-component">
    <form class="form-horizontal" id="paper_wallet_form">
        <h4><i class="fa fa-check-square-o fa-fw" aria-hidden="true"></i>Generate a Paper Wallet
        </h4>
        <fieldset>
            <div class="col-md-12">
                <div class="form-group">
                    <button class="btn btn-default" type="button" id="generate_paper_wallet">
                        <span class="fa fa-search"></span> Generate
                    </button>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div id="paperwallet_result">
                    </div>
                </div>
            </div>
        </fieldset>
    </form>
</div>

<script>
    var xhrGetTransaction, xhrDecodeTransaction;
    var transactionHash = $("#transaction_hash");
    var privateKey = $("#private_key");
    var publicAddress = $("#public_address");

    currentPage = {
        destroy: function () {
            if (xhrGetTransaction) xhrGetTransaction.abort();
            if (xhrDecodeTransaction) xhrDecodeTransaction.abort();
        },
        init: function () {
            $("#paperwallet_result").hide();
        },
        update: function () {
        }
    };

    $("#check_transaction").click(function () {
        if (!transactionHash.val() || !privateKey.val() || !publicAddress.val()) {
            alertError("Fill all fields!");
            return;
        }

        //get the transaction for the hash
        xhrGetTransaction = $.ajax({
            url: './api/daemon/get_transactions/?hash[]=' + transactionHash.val().trim(),
            dataType: 'json',
            cache: 'false',
            success: function (data1) {
                if (data1.result.length == 0) {
                    alertError("No transaction matching that hash");
                } else {

                    //decode the amount in the transaction hash
                    xhrDecodeTransaction = $.ajax({
                        url: './api/daemon/decode_outputs/?hash[]=' + transactionHash.val().trim() + '&viewkey=' + privateKey.val().trim() + '&address=' + publicAddress.val(),
                        dataType: 'json',
                        cache: 'false',
                        success: function (data2) {

                            if (data2.result.decoded_outs.length == 0) {
                                alertError("Could not decode transaction");
                            } else {
                                var tx = data1.result[0];
                                var dec_out = data2.result.decoded_outs[0];
                                var msg = 'Transaction decoded!<br />' +
                                    'Hash:' + dec_out.tx_hash + '<br />' +
                                    'Amount:' + getReadableCoins(dec_out.amount, 4) + '<br />' +
                                    'Height:' + tx.block_height + '<br />' +
                                    'Time:' + formatDate(tx.block_timestamp);
                                alertSuccess(msg);
                            }
                        }
                    });
                }
            }
        });
    });

    $("#create_int_addr").click(function () {
        var publicAddress = $("#integrated_public_address");

        if (!publicAddress.val()) {
            alertError("Fill all fields!");
            return;
        }

        var a = publicAddress.val();
        try {
            var res = create_integrated_address_rand(a);
            var msg = 'Address Created!<br />' +
                'Address:' + res.address + '<br />' +
                'Payment ID:' + res.paymentId

            alertSuccess(msg);
        }
        catch (e) {
            alertError("Could not create address:<br />" + e);
        }
    });

    $("#validate_address").click(function () {
        var publicAddress = $("#validate_public_address");

        if (!publicAddress.val()) {
            alertError("Fill all fields!");
            return;
        }

        var a = publicAddress.val();

        try {
            var res = decode_address(a);
            if (!res)
                alertError('Could not decode address');
            else {
                var msg = 'Address is valid!<br />' +
                    'Type:' + res.type.address_type + '<br />' +
                    'View:' + res.view + '<br />' +
                    'Spend:' + res.spend;

                if (res.type.address_type == "Integrated")
                    msg += '<br />PayID:' + res.intPaymentId;

                alertSuccess(msg);
            }
        }
        catch (e) {
            alertError("Could not decode address:<br />" + e);
        }
    });

    $("#generate_paper_wallet").click(function () {
        seed = cnUtil.sc_reduce32(cnUtil.rand_32());
        keys = cnUtil.create_address(seed);
        var mnemonic = mn_encode(seed, "english");

        $("#paperwallet_result").empty();

        $("#paperwallet_result").append(
            '<div><br/>' +
            '<strong>Address</strong><br/>' +
            '<p>' + cnUtil.pubkeys_to_string(keys.spend.pub, keys.view.pub) + '</p>' +
            '<strong>Mnemonic Seed</strong><br/>' +
            '<p>' + mnemonic + '</p>' +
            '<strong>View Keys</strong><br/>' +
            '<p>Public: ' + keys.view.pub + '</p>' +
            '<p>Secret: ' + keys.view.sec + '</p>' +
            '<strong>Spend Keys</strong><br/>' +
            '<p>Public: ' + keys.spend.pub + '</p>' +
            '<p>Secret: ' + keys.spend.sec + '</p>' +
            '<p class="alert-danger">NOTICE: Please be sure to verify the paper wallet before transferring funds. Funds cannot be recovered if the paper wallet is incorrect.</p>' +
            '</div>'
        );

        $("#paperwallet_result").show();
    });

    $(function () {
        $('[data-toggle="tooltip"]').tooltip();
    });
//# sourceURL=./pages/tools.php
</script>