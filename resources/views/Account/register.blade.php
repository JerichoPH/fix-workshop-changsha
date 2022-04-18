@extends('Layout.login')
@section('title')
    注册
@endsection
@section('content')
    <div class="register-box">
        <div class="register-logo" style="font-size: 20px;">
            <a href="{{url('/')}}">检修车间器材全生命周期管理系统</a>
        </div>

        <div class="register-box-body">
            <p class="login-box-msg">注册</p>
            @include('Layout.alert')
            <form action="{{url('/register')}}" method="post">
                {{csrf_field()}}
                <div class="form-group has-feedback">
                    <input
                            name="account"
                            type="text"
                            class="form-control"
                            placeholder="账号"
                            value="{{old('account')}}"
                            required autofocus>
                    <span class="fa fa-user form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input
                            name="nickname"
                            type="text"
                            class="form-control"
                            placeholder="姓名"
                            value="{{old('nickname')}}" required>
                    <span class="fa fa-etsy form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input
                            name="password"
                            type="password"
                            class="form-control"
                            placeholder="密码"
                            required>
                    <span class="fa fa-lock form-control-feedback"></span>
                </div>
                <div class="form-group has-feedback">
                    <input
                            name="identity_code"
                            type="text"
                            class="form-control"
                            placeholder="员工编号"
                            required>
                    <span class="fa fa-lock form-control-feedback"></span>
                </div>
                <div class="form-group">
                    <label style="font-weight: normal;"><input type="radio" name="supervision" value="1">验收</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label style="font-weight: normal;"><input type="radio" name="supervision" value="0" checked>非验收</label>
                </div>
                <div class="form-group">
                    <label style="font-weight: normal;">数据读取范围：</label>
                    <label style="font-weight: normal;"><input type="radio" name="read_scope" value="1" checked>个人</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label style="font-weight: normal;"><input type="radio" name="read_scope" value="2">工区</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label style="font-weight: normal;"><input type="radio" name="read_scope" value="3">车间</label>
                </div>
                <div class="form-group">
                    <label style="font-weight: normal;">数据操作范围：</label>
                    <label style="font-weight: normal;"><input type="radio" name="write_scope" value="1">个人</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label style="font-weight: normal;"><input type="radio" name="write_scope" value="0">工区</label>&nbsp;&nbsp;&nbsp;&nbsp;
                    <label style="font-weight: normal;"><input type="radio" name="write_scope" value="0">车间</label>
                </div>
                <div class="row">
                    <div class="col-xs-8">
                        {{--<div class="checkbox icheck">--}}
                        {{--<label>--}}
                        {{--<input type="checkbox">&nbsp;&nbsp;同意<a href="#">注册协议</a>--}}
                        {{--</label>--}}
                        {{--</div>--}}
                        <a href="{{url('/login')}}" class="text-center">已有账号，去登录</a>
                    </div>
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">&nbsp;&nbsp;注&nbsp;&nbsp;册&nbsp;&nbsp;</button>
                    </div>
                </div>
            </form>

            {{--<div class="social-auth-links text-center">--}}
            {{--<p>- OR -</p>--}}
            {{--<a href="#" class="btn btn-block btn-social btn-facebook btn-flat"><i class="fa fa-facebook"></i> Sign up using--}}
            {{--Facebook</a>--}}
            {{--<a href="#" class="btn btn-block btn-social btn-google btn-flat"><i class="fa fa-google-plus"></i> Sign up using--}}
            {{--Google+</a>--}}
            {{--</div>--}}

        </div>
        <!-- /.form-box -->
    </div>
@endsection
