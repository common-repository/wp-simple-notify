<?php

?>
    <div id="wp-simple-notify-container" class="container">
        <div class="row">
            <div class="col-12">
                <div class="jumbotron jumbotron-fluid p-m">
                    <div class="container-fluid text-center">
                        <h1 class="display-4">WP Simple Notify Settings</h1>
                        <p class="lead">Here you can configure all the options available for this plugin.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <h3 class=""><span class="badge badge-primary">Email config</span></h3>
                <form @submit.prevent="save">
                    <div class="form-group">
                        <label>Email address from:</label>
                        <input type="email" class="form-control" placeholder="@" v-model="config.email_from"
                               required>
                    </div>
                    <div class="form-group" v-if="!customSmtp">
                        <label>Email password:</label>
                        <input type="password" class="form-control" placeholder="*" v-model="config.email_pwd"
                               v-if="!defined_pwd"
                               required>
                        <input type="text" class="form-control" placeholder="Password defined from wp-config file"
                               readonly
                               v-else>
                    </div>
                    <div class="form-group">
                        <label>Sender name:</label>
                        <input type="text" class="form-control" placeholder="name" v-model="config.sender"
                               required>
                    </div>
                    <div class="form-group">
                        <label>SMTP Host:</label>
                        <input type="text" class="form-control" placeholder="smtp.yourdomain.com" v-model="config.host"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Port Number:</label>
                        <input type="number" class="form-control" plpasswordaceholder="port" v-model="config.port"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Connection security:</label>
                        <select class="form-control text-uppercase" v-model="config.secure" required>
                            <option v-for="s in security" :selected="s==config.secure">{{s}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Use SMTP authentication
                            <input type="checkbox" v-model="customSmtp" value="1">
                        </label>
                    </div>
                    <div v-if="customSmtp">
                        <div class="form-group">
                            <label>SMTP User:</label>
                            <input type="text" class="form-control" placeholder="user" v-model="config.smtp_user"
                                   required>
                        </div>
                        <div class="form-group">
                            <label>SMTP password:</label>
                            <input type="password" class="form-control" placeholder="password"
                                   v-model="config.smtp_pwd"
                                   v-if="!defined_pwd"
                                   required>
                            <input type="text" class="form-control"
                                   placeholder="Password defined from wp-config file"
                                   readonly
                                   v-else>
                        </div>
                    </div>

                    <div class="alert alert-secondary" role="alert" v-if="!defined_pwd">
                        Please remember that you can also setup the <b>EMAIL / SMTP password</b> by creating a <b>CONSTANT</b>
                        inside the <code>wp-config</code>
                        file for safety:
                        <br><code>define( 'WSN_EMAIL_PWD', 'my-password' );</code>
                    </div>

                    <div>
                        <button type="button" class="float-right btn btn-dark btn-lg mb-x mx-2"
                                :class="{disabled: sending}"
                                v-if="isReady"
                                @click="test">
                            <i class="fa fa-paper-plane" v-if="!sending"></i>
                            <i class="fa fa-spinner fa-spin" v-else></i>
                            Make a sending test
                        </button>
                        <button type="submit" class="float-right btn btn-success btn-lg mb-x mx-2"
                                :class="{disabled: saving}">
                            <i class="fa fa-cog" v-if="!saving"></i>
                            <i class="fa fa-spinner fa-spin" v-else></i>
                            Save configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="row" v-if="messageClass">
            <div class="col-12">
                <div class="alert alert-dismissible my-3 fade show" :class="messageClass"
                     role="alert">
                    <strong v-html="successMsg || errorMsg"></strong>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12 mt-3">
                <h3 class=""><span class="badge badge-warning">Plugin actions</span></h3>
                <div class="list-group mt-5">
                    <span href="" class="list-group-item list-group-item-action" v-for="(action, index) in actions">
                        <label>{{action.text}}</label>
                        <button class="btn float-right my-m mx-m" :class="action.active | status_button"
                                :class="{disabled: action.saving}"
                                @click.stop="set(index, action)">{{ action.active | status_label }}</button>
                        <span class="badge badge-pill float-right my-2 mx-4" :class="action.active | status_badge"
                              v-if="!action.saving">{{ action.active ? 'ON' : 'OFF' }}</span>
                        <i class="fa fa-spinner fa-spin float-right my-2 mx-4" v-else></i>
                    </span>
                </div>
            </div>
        </div>

<?php do_action( 'wp-simple-notify-settings-end' ); ?>