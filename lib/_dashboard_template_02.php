						<div class="row">
							<div class="col-xs-12 col-sm-12">
                                <div class="tabbable">
                                    <ul class="nav nav-tabs padding-18 tab-size-bigger" id="myTab">
                                        <li class="active">
                                            <a data-toggle="tab" href="#faq-tab-1">Piutang Customer Bulan Ini</a>
                                        </li>
                                        <li>
                                            <a data-toggle="tab" href="#faq-tab-2">Penjualan Hari Ini</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content no-border padding-24">
                                        <div id="faq-tab-1" class="tab-pane fade in active">
                                            <?php include "_dashboard_piutang.php"; ?>
                                        </div>
                                        <div id="faq-tab-2" class="tab-pane fade">
                                            <?php include "_dashboard_penjualan.php"; ?>
                                        </div>
                                    </div>
                                </div>                            
                            </div>
                        </div>