package com.internousdev.ecsite.action;

import java.util.Map;

import org.apache.struts2.interceptor.SessionAware;

import com.internousdev.ecsite.dao.ItemDetailDAO;
import com.internousdev.ecsite.dto.ItemDetailDTO;
import com.opensymphony.xwork2.ActionSupport;


public class ItemDetailAction extends ActionSupport implements SessionAware{
//	private List<ItemDetailDTO> itemDetailList = new ArrayList<ItemDetailDTO>();
	private Map<String, Object> session;
	private String id;
	public String execute() {
		System.out.println(id);
		String result = ERROR;
		ItemDetailDAO itemDetailDAO = new ItemDetailDAO();
		ItemDetailDTO itemDetailDTO = new ItemDetailDTO();
		itemDetailDTO = itemDetailDAO.getItemInfoList(id);

		System.out.println(itemDetailDTO.getId());
		System.out.println(itemDetailDTO.getItemName());
		System.out.println(itemDetailDTO.getItemStock());
		System.out.println(itemDetailDTO.getInsert_date());

		session.put("id", itemDetailDTO.getId());
		session.put("itemName", itemDetailDTO.getItemName());
		session.put("itemPrice", itemDetailDTO.getItemPrice());
		session.put("itemStock", itemDetailDTO.getItemStock());
		session.put("insert_date", itemDetailDTO.getInsert_date());


		if(itemDetailDTO!=null) {
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
