package com.internousdev.ecsite.action;

import com.opensymphony.xwork2.ActionSupport;
import org.apache.struts2.interceptor.SessionAware;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.Map;
import com.internousdev.ecsite.dao.UserListDAO;
import com.internousdev.ecsite.dto.UserListDTO;

public class UserListAction extends ActionSupport implements SessionAware{
	public Map<String,Object> session;
	private UserListDAO userListDAO=new UserListDAO();
	private ArrayList<UserListDTO> userList=new ArrayList<UserListDTO>();
	private String deleteFlg;
	private String message;

	public String execute() throws SQLException{
		String result;
		if(!session.containsKey("id")){
			result= ERROR;
		}

		if(deleteFlg==null){

			userList=userListDAO.getUserInfo();
			session.put("userList", userList);
		}else if(deleteFlg.equals("1")){
			delete();
		}

		result=SUCCESS;
		return result;
	}

	public void delete() throws SQLException{
		String login_id=session.get("loginId").toString();
		String login_pass=session.get("loginPass").toString();
		String user_name=session.get("userName").toString();
		int res=userListDAO.userHistoryDelete(login_id,login_pass,user_name);

		if(res>0){
			userList=null;
			setMessage("商品情報を正しく削除しました。");
		}else if(res==0){
			setMessage("商品情報の削除に失敗しました。");
		}
	}

	public void setDeleteFlg(String deleteFlg){
		this.deleteFlg=deleteFlg;
	}
	@Override
	public void setSession(Map<String,Object> session){
		this.session=session;
	}
	public ArrayList<UserListDTO> getUserList(){
		return this.userList;
	}
	public String getMessage(){
		return this.message;
	}
	public void setMessage(String message){
		this.message=message;
	}


}

