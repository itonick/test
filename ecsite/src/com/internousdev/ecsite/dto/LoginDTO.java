package com.internousdev.ecsite.dto;

public class LoginDTO {
	public String loginId;
	public String loginPassword;
	public String userName;
	public boolean loginFig;

	public String getLoginId(){
		return loginId;
	}
	public void setLoginId(String loginId) {
		this.loginId=loginId;
	}

	public String getLoginPassword(){
		return loginPassword;
	}
	public void setLoginPassword(String loginPassword) {
		this.loginPassword=loginPassword;
	}

	public String getUserName(){
		return userName;
	}
	public void setUserName(String userName) {
		this.userName=userName;
	}

	public boolean getLoginFig(){
		return loginFig;
	}
	public void setLoginFig(boolean loginFig) {
		this.loginFig=loginFig;
	}

}
