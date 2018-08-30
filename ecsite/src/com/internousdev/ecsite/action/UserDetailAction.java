package com.internousdev.ecsite.action;

import java.util.Map;

import org.apache.struts2.interceptor.SessionAware;

import com.internousdev.ecsite.dao.UserDetailDAO;
import com.internousdev.ecsite.dto.UserDetailDTO;
import com.opensymphony.xwork2.ActionSupport;

public class UserDetailAction extends ActionSupport implements SessionAware{
	private Map<String, Object> session;
	private String id;
	public String execute() {
		System.out.println(id);
		String result = ERROR;
		UserDetailDAO userDetailDAO = new UserDetailDAO();
		UserDetailDTO userDetailDTO = new UserDetailDTO();
		userDetailDTO = userDetailDAO.getUserInfoList(id);

		System.out.println(userDetailDTO.getId());
		System.out.println(userDetailDTO.getLoginId());
		System.out.println(userDetailDTO.getLoginPass());
		System.out.println(userDetailDTO.getUserName());
		System.out.println(userDetailDTO.getInsert_date());

		session.put("id", userDetailDTO.getId());
		session.put("loginId", userDetailDTO.getLoginId());
		session.put("loginPass", userDetailDTO.getLoginPass());
		session.put("userName", userDetailDTO.getUserName());
		session.put("insert_date", userDetailDTO.getInsert_date());

		if(userDetailDTO!=null) {
			result = SUCCESS;
		}
		return result;

	}

	public String getId() {
		return id;
	}

	public void setId(String id) {
		this.id = id;
	}

	public void setSession(Map<String, Object> session) {
		this.session = session;
	}
	public Map<String, Object> getSession() {
		return session;
	}


}
